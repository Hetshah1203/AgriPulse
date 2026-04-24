import pandas as pd
import mysql.connector

# ----------------------------
# 1. Load Market Data CSV
# ----------------------------
df = pd.read_csv("market_data.csv")

# Clean column names (remove spaces, lowercase)
df.columns = (
    df.columns.str.strip()
              .str.replace(" ", "_")
              .str.replace("-", "_")
              .str.lower()
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

cursor = conn.cursor(buffered=True)

# ----------------------------
# 3. Insert into Market_Data table
# ----------------------------
for row in df.itertuples(index=False):
    cursor.execute("""
        INSERT INTO Market_Data (
            state_name, district_name, market_name, variety, crop_group,
            arrival_tonnes, min_price_rs_quintal, max_price_rs_quintal
        ) VALUES (%s, %s, %s, %s, %s, %s, %s, %s)
    """, (
        row.state_name,
        row.district_name,
        row.market_name,
        row.variety,
        row.group,   # ⚠️ "group" in CSV → becomes "group" in df (renamed from original)
        row.arrival_tonnes,
        row.min_price_rs_quintal,
        row.max_price_rs_quintal
    ))

# ----------------------------
# 4. Commit & Close
# ----------------------------
conn.commit()
cursor.close()
conn.close()

print("✅ Market data inserted successfully into Market_Data table!")
