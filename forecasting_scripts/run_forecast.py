# run_forecast.py

import pandas as pd
from prophet import Prophet
import matplotlib.pyplot as plt
import json
import os  # Import the os library to handle file paths
from datetime import datetime
import math

print("--- Starting Full Forecasting Analysis ---"),

# Define the location of this script and where to find the data
# This makes the script runnable from anywhere on your system
script_directory = os.path.dirname(os.path.abspath(__file__))
csv_file_path = os.path.join(script_directory, 'data', 'historical_data.csv')
output_plot_directory = os.path.join(script_directory, '..', 'public', 'images', 'forecasts')
output_json_path = os.path.join(script_directory, 'forecast_results.json')

# --- DATA PREPARATION ---
try:
    # Ensure the output directory for plots exists
    os.makedirs(output_plot_directory, exist_ok=True)
    
    # Load the cleaned CSV data
    df = pd.read_csv(csv_file_path)
    print(f"Successfully loaded CSV from: {csv_file_path}")
except FileNotFoundError:
    print(f"FATAL ERROR: CSV file not found at '{csv_file_path}'. Place the CSV in a 'data' subfolder.")
    exit()

# Prepare the dataframe for Prophet
df = df.rename(columns={'Date': 'ds', 'Quantity_Sold': 'y'})
df['ds'] = pd.to_datetime(df['ds'], format='%d/%m/%Y')

# Use product_id if present (so renames don't break forecast lookup); else group by Egg_Size (legacy)
use_product_id = 'product_id' in df.columns
if use_product_id:
    # Key forecasts by product_id so renaming egg size in admin doesn't break the match
    product_ids = [pid for pid in df['product_id'].unique() if pd.notna(pid)]
    # Exclude damaged: drop rows where Egg_Size contains DAMAGED, then get unique product_ids
    df_no_damaged = df[~df['Egg_Size'].astype(str).str.upper().str.contains('DAMAGED|DAMAGE', na=False)]
    PRODUCT_KEYS = [str(pid) for pid in df_no_damaged['product_id'].unique() if pd.notna(pid)]
    # For each product_id we need the display name (first Egg_Size for that id)
    product_id_to_name = df_no_damaged.groupby('product_id')['Egg_Size'].first().to_dict()
else:
    # Legacy: group by Egg_Size
    def normalize_egg_size(name):
        name_upper = str(name).upper().strip()
        mapping = {'XL': 'X-LARGE', 'X LARGE': 'X-LARGE', 'X-LARGE': 'X-LARGE', 'PULLETS': 'PULLETS',
                   'PULLET': 'PULLETS', 'SMALL': 'SMALL', 'MEDIUM': 'MEDIUM', 'LARGE': 'LARGE',
                   'JUMBO': 'JUMBO', 'PEWEE': 'PEWEE'}
        if name_upper in mapping:
            return mapping[name_upper]
        for key, value in mapping.items():
            if key in name_upper or name_upper in key:
                return value
        return name_upper
    unique_sizes = df['Egg_Size'].unique()
    normalized_sizes = {}
    for size in unique_sizes:
        normalized = normalize_egg_size(size)
        if normalized not in normalized_sizes:
            normalized_sizes[normalized] = size
    EGG_SIZES = [s for s in normalized_sizes.keys() if 'DAMAGED' not in s.upper() and 'DAMAGE' not in s.upper()]
    PRODUCT_KEYS = None  # use EGG_SIZES + normalized_sizes below

all_forecasts = {}
generated_at = datetime.utcnow().isoformat()

# --- LOOP: by product_id or by egg size ---
if use_product_id:
    iter_keys = PRODUCT_KEYS
else:
    iter_keys = EGG_SIZES

for key in iter_keys:
    if use_product_id:
        forecast_df = df[df['product_id'].astype(str) == key].copy()
        display_name = product_id_to_name.get(int(key), key)
        print(f"\n--- Generating forecast for product_id={key} ({display_name}) ---")
    else:
        original_size = normalized_sizes[key]
        forecast_df = df[(df['Egg_Size'] == original_size)].copy()
        display_name = original_size
        print(f"\n--- Generating forecast for: {key} (from '{original_size}') ---")

    if forecast_df.empty:
        print(f"Warning: No historical data found for '{display_name}'. Skipping.")
        continue
    
    # Initialize and train the model
    model = Prophet()
    model.fit(forecast_df[['ds', 'y']])

    # Get the last historical date and today's date
    last_historical_date = forecast_df['ds'].max()
    today = pd.Timestamp.now().normalize()
    
    # Calculate how many days we need to forecast from today
    days_from_last_to_today = (today - last_historical_date).days
    # We want 30 days of forecast from today, so add the gap plus 30
    periods_needed = max(30, days_from_last_to_today + 30)
    
    # Create future dataframe and generate forecast
    future = model.make_future_dataframe(periods=periods_needed)
    forecast = model.predict(future)

    # Filter forecast to only include dates from today onwards
    forecast_future = forecast[forecast['ds'] >= today].copy()
    
    # Take only the next 30 days from today
    forecast_future = forecast_future.head(30)

    # --- Extract the numerical forecast for tomorrow ---
    if not forecast_future.empty:
        tomorrow_forecast = forecast_future.iloc[0]
        forecast_value = tomorrow_forecast['yhat']
        rounded_forecast = round(forecast_value)
    else:
        # Fallback: use the last forecast value
        forecast_value = forecast.iloc[-1]['yhat']
        rounded_forecast = round(forecast_value)
    
    print(f"Forecast for tomorrow: {round(forecast_value)} pcs")

    # --- Save the forecast plot with a unique name ---
    plot_filename = f'forecast_plot_{key}.png'
    plot_filepath = os.path.join(output_plot_directory, plot_filename)
    
    fig = model.plot(forecast)
    plt.title(f'Sales Forecast for {display_name} Eggs')
    plt.xlabel('Date')
    plt.ylabel('Quantity Sold')
    plt.savefig(plot_filepath)
    plt.close(fig)  # Close the figure to free up memory
    print(f"Forecast plot saved to: {plot_filepath}")

    # --- Build structured forecast data for Laravel ---
    # Use the filtered future forecast (from today onwards, next 30 days)
    daily_records = []
    for _, row in forecast_future.iterrows():
        daily_records.append({
            'date': row['ds'].strftime('%Y-%m-%d'),
            'yhat': round(row['yhat'], 2),
            'yhat_lower': round(row['yhat_lower'], 2),
            'yhat_upper': round(row['yhat_upper'], 2),
        })

    # --- Evaluate model performance on available historical data ---
    evaluation_df = forecast[['ds', 'yhat']].merge(
        forecast_df[['ds', 'y']],
        on='ds',
        how='inner'
    )

    mae = None
    rmse = None
    if not evaluation_df.empty:
        errors = evaluation_df['yhat'] - evaluation_df['y']
        mae = float(errors.abs().mean())
        rmse = float(math.sqrt((errors ** 2).mean()))

    all_forecasts[key] = {
        'generated_at': generated_at,
        'history_last_date': forecast_df['ds'].max().strftime('%Y-%m-%d'),
        'plot': plot_filename,
        'tomorrow': rounded_forecast,
        'forecast': daily_records,
        'metrics': {
            'mae': round(mae, 2) if mae is not None else None,
            'rmse': round(rmse, 2) if rmse is not None else None,
            'test_points': len(evaluation_df)
        }
    }

# --- Save the final numerical results to a JSON file ---
with open(output_json_path, 'w') as f:
    json.dump(all_forecasts, f, indent=4)

print(f"\nAll numerical forecasts saved successfully to: {output_json_path}")
print("\n--- Forecasting Analysis Complete ---")