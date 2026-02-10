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

# Normalize egg size names: map variations to standard uppercase keys
def normalize_egg_size(name):
    """Normalize egg size names to standard uppercase format"""
    name_upper = str(name).upper().strip()
    
    # Mapping variations to standard names
    mapping = {
        'XL': 'X-LARGE',
        'X LARGE': 'X-LARGE',
        'X-LARGE': 'X-LARGE',
        'PULLETS': 'PULLETS',
        'PULLET': 'PULLETS',
        'SMALL': 'SMALL',
        'MEDIUM': 'MEDIUM',
        'LARGE': 'LARGE',
        'JUMBO': 'JUMBO',
        'PEWEE': 'PEWEE',
    }
    
    # Try exact match first
    if name_upper in mapping:
        return mapping[name_upper]
    
    # Try partial match
    for key, value in mapping.items():
        if key in name_upper or name_upper in key:
            return value
    
    # Return uppercase version if no mapping found
    return name_upper

# Get unique egg sizes from CSV and normalize them
unique_sizes = df['Egg_Size'].unique()
normalized_sizes = {}
for size in unique_sizes:
    normalized = normalize_egg_size(size)
    if normalized not in normalized_sizes:
        normalized_sizes[normalized] = size  # Store original for filtering

# Exclude damaged eggs
EGG_SIZES = [size for size in normalized_sizes.keys() 
             if 'DAMAGED' not in size.upper() and 'DAMAGE' not in size.upper()]

all_forecasts = {}
generated_at = datetime.utcnow().isoformat()

# --- LOOP THROUGH EACH EGG SIZE ---
for normalized_size in EGG_SIZES:
    original_size = normalized_sizes[normalized_size]
    print(f"\n--- Generating forecast for: {normalized_size} (from '{original_size}') ---")
    
    # Filter by original size name (case-sensitive match with CSV)
    forecast_df = df[(df['Egg_Size'] == original_size)].copy()

    if forecast_df.empty:
        print(f"Warning: No historical data found for '{original_size}'. Skipping.")
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
    plot_filename = f'forecast_plot_{normalized_size}.png'
    plot_filepath = os.path.join(output_plot_directory, plot_filename)
    
    fig = model.plot(forecast)
    plt.title(f'Sales Forecast for {normalized_size} Eggs')
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

    all_forecasts[normalized_size] = {
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