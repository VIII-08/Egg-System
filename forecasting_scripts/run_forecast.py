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

# Define the egg sizes we want to generate forecasts for
EGG_SIZES = ['SMALL', 'MEDIUM', 'LARGE', 'X-LARGE', 'JUMBO', 'PEWEE', 'PULLETS']

all_forecasts = {}
generated_at = datetime.utcnow().isoformat()

# --- LOOP THROUGH EACH EGG SIZE ---
for egg_size in EGG_SIZES:
    print(f"\n--- Generating forecast for: {egg_size} ---")
    
    forecast_df = df[(df['Egg_Size'] == egg_size)].copy()

    if forecast_df.empty:
        print(f"Warning: No historical data found for '{egg_size}'. Skipping.")
        continue
    
    # Initialize and train the model
    model = Prophet()
    model.fit(forecast_df[['ds', 'y']])

    # Create future dataframe and generate forecast
    future = model.make_future_dataframe(periods=30)
    forecast = model.predict(future)

    # --- Extract the numerical forecast for tomorrow ---
    tomorrow_forecast = forecast.iloc[-30]
    forecast_value = tomorrow_forecast['yhat']
    rounded_forecast = round(forecast_value)
    
    print(f"Forecast for tomorrow: {round(forecast_value)} pcs")

    # --- Save the forecast plot with a unique name ---
    plot_filename = f'forecast_plot_{egg_size}.png'
    plot_filepath = os.path.join(output_plot_directory, plot_filename)
    
    fig = model.plot(forecast)
    plt.title(f'Sales Forecast for {egg_size} Eggs')
    plt.xlabel('Date')
    plt.ylabel('Quantity Sold')
    plt.savefig(plot_filepath)
    plt.close(fig)  # Close the figure to free up memory
    print(f"Forecast plot saved to: {plot_filepath}")

    # --- Build structured forecast data for Laravel ---
    forecast_tail = forecast[['ds', 'yhat', 'yhat_lower', 'yhat_upper']].tail(30)
    daily_records = []
    for _, row in forecast_tail.iterrows():
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

    all_forecasts[egg_size] = {
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