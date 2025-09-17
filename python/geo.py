from flask import Flask, request, jsonify
import requests
from geopy.distance import geodesic

app = Flask(__name__)

# Convert postcode -> lat/lng using postcodes.io
def geocode_postcode(postcode: str):
    if not postcode:
        return None, None
    pc = postcode.replace(" ", "")
    url = f"https://api.postcodes.io/postcodes/{pc}"
    try:
        r = requests.get(url, timeout=10)
        if r.status_code == 200:
            data = r.json()
            if data.get("status") == 200 and data.get("result"):
                return data["result"]["latitude"], data["result"]["longitude"]
    except Exception as e:
        print("Geocode error:", e)
    return None, None

@app.route("/compute", methods=["POST"])
def compute():
    payload = request.get_json(force=True)
    officers = payload.get("officers", [])

    enriched = []
    coords = []

    for o in officers:
        pc = o.get("postcode")
        lat, lng = geocode_postcode(pc)
        o["lat"], o["lng"] = lat, lng
        enriched.append(o)
        if lat and lng:
            coords.append((lat, lng))

    pairs = []
    for i in range(len(coords)):
        for j in range(i+1, len(coords)):
            km = geodesic(coords[i], coords[j]).km
            pairs.append(km)

    stats = {
        "count_with_pc": sum(1 for o in enriched if o.get("postcode")),
        "count_without_pc": sum(1 for o in enriched if not o.get("postcode")),
        "count_pairs": len(pairs),
        "min_km": min(pairs) if pairs else None,
        "avg_km": (sum(pairs)/len(pairs)) if pairs else None,
        "max_km": max(pairs) if pairs else None,
    }

    return jsonify({
        "officers": enriched,
        "distance_matrix": pairs,
        "stats": stats
    })

if __name__ == "__main__":
    app.run(port=5000)
