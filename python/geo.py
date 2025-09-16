#!/usr/bin/env python3
import sys, json, math, re, time

try:
    import pgeocode  # offline geocoder for GB
except Exception as e:
    # If import fails, we still return a valid JSON stub later
    pgeocode = None

# Optional online fallback (only if offline fails)
try:
    import requests
except Exception:
    requests = None


PC_RE = re.compile(r"[A-Z]{1,2}\d{1,2}[A-Z]?\s*\d[A-Z]{2}")

def normalize_pc(pc: str | None) -> str | None:
    if not pc:
        return None
    pc = pc.strip().upper()
    # extract the most postcode-looking bit
    m = PC_RE.search(pc)
    if not m:
        return None
    pc = m.group(0)
    # ensure single space before final 3 chars (inward code)
    pc = re.sub(r"\s+", "", pc)
    if len(pc) > 3:
        pc = pc[:-3] + " " + pc[-3:]
    return pc


def haversine_km(lat1, lon1, lat2, lon2):
    R = 6371.0088
    phi1 = math.radians(lat1); phi2 = math.radians(lat2)
    dphi = math.radians(lat2 - lat1)
    dlmb = math.radians(lon2 - lon1)
    a = math.sin(dphi/2)**2 + math.cos(phi1)*math.cos(phi2)*math.sin(dlmb/2)**2
    c = 2 * math.atan2(math.sqrt(a), math.sqrt(1-a))
    return round(R * c, 2)


def geocode_offline(pc: str):
    """Try pgeocode (offline). Returns (lat, lon) or (None, None)."""
    if not pgeocode:
        return (None, None)
    nomi = pgeocode.Nominatim('GB')
    res = nomi.query_postal_code(pc)
    # `res` can be a Series with NaNs
    try:
        lat = float(res.latitude)
        lon = float(res.longitude)
        if math.isnan(lat) or math.isnan(lon):
            return (None, None)
        return (lat, lon)
    except Exception:
        return (None, None)


def geocode_online(pc: str):
    """Fallback using postcodes.io. Returns (lat, lon) or (None, None)."""
    if not requests:
        return (None, None)
    try:
        r = requests.get(
            f"https://api.postcodes.io/postcodes/{pc.replace(' ', '%20')}",
            timeout=6,
        )
        if r.status_code != 200:
            return (None, None)
        j = r.json()
        if j.get("status") != 200:
            return (None, None)
        result = j.get("result") or {}
        lat = result.get("latitude")
        lon = result.get("longitude")
        return (lat, lon) if lat is not None and lon is not None else (None, None)
    except Exception:
        return (None, None)


def main():
    raw = sys.stdin.read()
    try:
        payload = json.loads(raw) if raw else {}
    except Exception:
        payload = {}

    officers = payload.get("officers") or []
    out_officers = []

    count_with_pc = 0
    count_without_pc = 0

    # 1) geocode everyone
    for o in officers:
        pc_in = o.get("postcode")
        pc = normalize_pc(pc_in)
        lat = None; lon = None; status = "missing"
        if pc:
            # offline first
            lat, lon = geocode_offline(pc)
            if lat is not None and lon is not None:
                status = "ok"
            else:
                # online fallback
                lat, lon = geocode_online(pc)
                status = "ok" if (lat is not None and lon is not None) else "failed"

        if status == "ok":
            count_with_pc += 1
        else:
            count_without_pc += 1

        out = dict(o)
        out["postcode"]   = pc  # normalized or None
        out["lat"]        = lat
        out["lng"]        = lon
        out["geo_status"] = status
        out_officers.append(out)

    # 2) build pairwise distances only across OK coords
    coords = [(i, x["lat"], x["lng"]) for i, x in enumerate(out_officers)
              if x.get("geo_status") == "ok" and x.get("lat") is not None and x.get("lng") is not None]

    n = len(out_officers)
    matrix = [[None for _ in range(n)] for _ in range(n)]
    dists = []
    for i in range(n):
        oi = out_officers[i]
        if oi.get("geo_status") != "ok": continue
        for j in range(i+1, n):
            oj = out_officers[j]
            if oj.get("geo_status") != "ok": continue
            d = haversine_km(oi["lat"], oi["lng"], oj["lat"], oj["lng"])
            matrix[i][j] = matrix[j][i] = d
            dists.append(d)

    stats = {
        "count_with_pc": count_with_pc,
        "count_without_pc": count_without_pc,
        "count_pairs": len(dists),
        "min_km": min(dists) if dists else None,
        "avg_km": round(sum(dists)/len(dists), 2) if dists else None,
        "max_km": max(dists) if dists else None,
    }

    print(json.dumps({
        "officers": out_officers,
        "distance_matrix": matrix,
        "stats": stats,
    }, ensure_ascii=False))
    sys.stdout.flush()


if __name__ == "__main__":
    main()
