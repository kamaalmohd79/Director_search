from flask import Flask, request, jsonify
from geopy.distance import geodesic

app = Flask(__name__)

@app.route("/compute", methods=["POST"])
def compute():
    data = request.json
    officers = data.get("officers", [])

    # collect only officers with lat/lng
    valid = [(i, o) for i, o in enumerate(officers) if o.get("lat") and o.get("lng")]

    pairs = []
    distances = []

    # calculate all pairs
    for i in range(len(valid)):
        for j in range(i + 1, len(valid)):
            idx1, o1 = valid[i]
            idx2, o2 = valid[j]
            km = geodesic((o1["lat"], o1["lng"]), (o2["lat"], o2["lng"])).km
            km = round(km, 2)
            pairs.append({"i": idx1, "j": idx2, "from": o1["name"], "to": o2["name"], "km": km})
            distances.append(km)

    stats = {}
    if distances:
        stats = {
            "count_pairs": len(pairs),
            "min_km": min(distances),
            "avg_km": round(sum(distances) / len(distances), 2),
            "max_km": max(distances),
        }

    return jsonify({
        "officers": officers,
        "pairs": pairs,
        "distance_matrix": [],  # optional
        "stats": stats
    })


if __name__ == "__main__":
    app.run(host="127.0.0.1", port=5000, debug=True)
