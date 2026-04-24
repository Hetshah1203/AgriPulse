import pandas as pd
import mysql.connector

# ----------------------------
# 1. Load dataset
# ----------------------------
df = pd.read_csv("agriculture_dataset.csv")

# Clean column names (replace spaces, parentheses with underscores)
df.columns = (
    df.columns.str.strip()
              .str.replace(r"[()]", "", regex=True)
              .str.replace(" ", "_")
)

print("Columns in dataset:", df.columns.tolist())

# ----------------------------
# 2. Connect to MySQL
# ----------------------------
conn = mysql.connector.connect(
    host="localhost",
    user="root",
    password="",   # 🔹 change this
    database="agriculture_db"   # 🔹 change this
)

# Use buffered cursor to avoid "Unread result found"
cursor = conn.cursor(buffered=True)

# ----------------------------
# 3. Insert into reference tables
# ----------------------------

# Crops
for crop, season in df[['Crop_Type', 'Season']].drop_duplicates().values:
    cursor.execute("INSERT IGNORE INTO Crops (crop_name, season) VALUES (%s, %s)", (crop, season))

# Irrigation Types
for irrigation in df['Irrigation_Type'].drop_duplicates().values:
    cursor.execute("INSERT IGNORE INTO Irrigation_Types (irrigation_type) VALUES (%s)", (irrigation,))

# Fertilizers (generic type)
cursor.execute("INSERT IGNORE INTO Fertilizers (fertilizer_type) VALUES (%s)", ("General",))

# Pesticides (generic type)
cursor.execute("INSERT IGNORE INTO Pesticides (pesticide_type) VALUES (%s)", ("General",))

# Soil Types
for soil in df['Soil_Type'].drop_duplicates().values:
    cursor.execute("INSERT IGNORE INTO Soil_Types (soil_type) VALUES (%s)", (soil,))

conn.commit()

# ----------------------------
# 4. Insert farm-level data
# ----------------------------
for row in df.itertuples(index=False):
    # Farms
    cursor.execute("""
        INSERT INTO Farms (farm_id, farm_area_acres) VALUES (%s, %s)
        ON DUPLICATE KEY UPDATE farm_area_acres = VALUES(farm_area_acres)
    """, (row.Farm_ID, row.Farm_Areaacres))

    # Get crop_id
    cursor.execute("SELECT crop_id FROM Crops WHERE crop_name=%s AND season=%s", (row.Crop_Type, row.Season))
    crop_result = cursor.fetchone()
    crop_id = crop_result[0] if crop_result else None

    if crop_id:
        # Farm_Crops
        cursor.execute("""
            INSERT INTO Farm_Crops (farm_id, crop_id, season, year)
            VALUES (%s, %s, %s, %s)
        """, (row.Farm_ID, crop_id, row.Season, 2024))

        # Fertilizer Usage
        cursor.execute("SELECT fertilizer_id FROM Fertilizers WHERE fertilizer_type='General'")
        fert_result = cursor.fetchone()
        if fert_result:
            fertilizer_id = fert_result[0]
            cursor.execute("""
                INSERT INTO Farm_Fertilizer_Usage (farm_id, fertilizer_id, quantity_tons)
                VALUES (%s, %s, %s)
            """, (row.Farm_ID, fertilizer_id, row.Fertilizer_Usedtons))

        # Pesticide Usage
        cursor.execute("SELECT pesticide_id FROM Pesticides WHERE pesticide_type='General'")
        pest_result = cursor.fetchone()
        if pest_result:
            pesticide_id = pest_result[0]
            cursor.execute("""
                INSERT INTO Farm_Pesticide_Usage (farm_id, pesticide_id, quantity_kg)
                VALUES (%s, %s, %s)
            """, (row.Farm_ID, pesticide_id, row.Pesticide_Usedkg))

        # Farm_Yield
        cursor.execute("""
            INSERT INTO Farm_Yield (farm_id, crop_id, yield_tons, water_usage_cubic_meters, year)
            VALUES (%s, %s, %s, %s, %s)
        """, (row.Farm_ID, crop_id, row.Yieldtons, row.Water_Usagecubic_meters, 2024))

# ----------------------------
# 5. Commit & Close
# ----------------------------
conn.commit()
cursor.close()
conn.close()

print("✅ Data inserted successfully into all tables!")
