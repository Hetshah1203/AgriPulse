import pandas as pd
import mysql.connector
from mysql.connector import Error

# --- Connect to MySQL ---
conn = mysql.connector.connect(
    host='localhost',
    user='root',
    password='',
    database='agriculture_db'
)
cursor = conn.cursor()
print("Connected to MySQL")

# --- Read CSV ---
df = pd.read_csv('new.csv', sep='\t')
df.columns = [col.strip().lower().replace(" ", "_") for col in df.columns]

# Handle 'inf' values
df['crop_yield'] = df['crop_yield'].apply(lambda x: None if str(x).lower() == 'inf' else float(x))

# --- Insert Vehicles (allow duplicates) ---
for vehicle in df['vehicle_type']:
    cursor.execute("INSERT INTO Vehicles (vehicle_type) VALUES (%s)", (vehicle,))
conn.commit()

# --- Insert Crops (allow duplicates) ---
for crop in df['crop_type']:
    cursor.execute("INSERT INTO Crops (crop_type) VALUES (%s)", (crop,))
conn.commit()

# --- Insert Harvests, Storage, Transportation ---
for _, row in df.iterrows():
    # Get crop_id (take last inserted one for duplicates)
    cursor.execute("SELECT crop_id FROM Crops WHERE crop_type=%s ORDER BY crop_id DESC LIMIT 1", (row['crop_type'],))
    crop_id = cursor.fetchone()[0]

    # Insert Harvests
    cursor.execute("""
        INSERT INTO Harvests (crop_id, harvest_date, crop_yield)
        VALUES (%s, STR_TO_DATE(%s, '%%d-%%m-%%Y'), %s)
    """, (crop_id, row['harvest_date'], row['crop_yield']))
    conn.commit()
    harvest_id = cursor.lastrowid

    # Insert Storage
    cursor.execute("""
        INSERT INTO Storage (harvest_id, storage_temperature, storage_humidity)
        VALUES (%s, %s, %s)
    """, (harvest_id, row['storage_temperature'], row['storage_humidity']))
    conn.commit()

    # Get vehicle_id (take last inserted one for duplicates)
    cursor.execute("SELECT vehicle_id FROM Vehicles WHERE vehicle_type=%s ORDER BY vehicle_id DESC LIMIT 1", (row['vehicle_type'],))
    vehicle_id = cursor.fetchone()[0]

    # Insert Transportation
    cursor.execute("""
        INSERT INTO Transportation (harvest_id, vehicle_id, date_time)
        VALUES (%s, %s, STR_TO_DATE(%s, '%%d-%%m-%%Y %%H:%%i'))
    """, (harvest_id, vehicle_id, row['date_and_time']))
    conn.commit()

print("Data inserted with duplicates successfully!")
cursor.close()
conn.close()
