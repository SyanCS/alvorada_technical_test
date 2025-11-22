# Quick Reference Guide

## 🚀 Starting the Application

```bash
# Start all services
docker-compose up -d

# View logs
docker-compose logs -f web

# Stop services
docker-compose down
```

## 🌐 Access Points

| Service | URL | Description |
|---------|-----|-------------|
| **Main Application** | http://localhost:8080 | Property intake form |
| **Interactive Map** | http://localhost:8080/map.html | View all properties on map |
| **Property Details** | http://localhost:8080/property?id=1 | View specific property |
| **pgAdmin** | http://localhost:8081 | Database management |

## 📡 API Endpoints

### Get Property Details
```bash
GET http://localhost:8080/api/property.php?id=1
```

### Get All Properties
```bash
GET http://localhost:8080/api/properties.php
```

### Add Note to Property
```bash
POST http://localhost:8080/api/add_note.php
Content-Type: application/json

{
  "property_id": 1,
  "note": "This is a research note"
}
```

### Get Property Notes
```bash
GET http://localhost:8080/api/notes.php?property_id=1
```

## 📁 Key Files

### Entry Points
- `/index.php` - Main form (root URL)
- `/public/map.html` - Map interface
- `/public/property.html` - Property details

### API Files
- `/public/api/property.php` - Property API
- `/public/api/properties.php` - All properties API
- `/public/api/add_note.php` - Add note API
- `/public/api/notes.php` - Notes API

### Configuration
- `/.env` - Environment variables
- `/src/Config/AppConfig.php` - App configuration
- `/docker-compose.yml` - Docker services

### Database
- `/sql/schema.sql` - Database schema

## 🔧 Common Tasks

### Add a Property
1. Visit http://localhost:8080
2. Fill in property name and address
3. Click "Add Property & Enrich Data"
4. View on map or add notes

### View All Properties on Map
1. Visit http://localhost:8080/map.html
2. Use search to filter properties
3. Click markers to see details
4. Click "View Details & Add Notes" in popup

### Add Notes to Property
1. Visit http://localhost:8080/property.html?id=1
2. Type note in text area
3. Click "Add Note"
4. Note appears in list below

### Database Access
1. Visit http://localhost:8081
2. Login with credentials from `.env`
3. Connect to server:
   - Host: `db`
   - Port: `5432`
   - Database: from `.env`
   - Username: from `.env`
   - Password: from `.env`

## 🐛 Troubleshooting

### Container won't start
```bash
docker-compose down
docker-compose up --build -d
```

### Database connection error
```bash
# Check if database is ready
docker-compose logs db

# Restart web container
docker-compose restart web
```

### Can't access application
```bash
# Check if containers are running
docker-compose ps

# Check web logs
docker-compose logs web
```

### Clear all data and restart
```bash
docker-compose down -v
docker-compose up -d
```

## 📊 Database Queries

### View all properties
```sql
SELECT 
    id, 
    name, 
    address,
    ST_Y(location::geometry) as latitude,
    ST_X(location::geometry) as longitude,
    created_at
FROM properties
ORDER BY created_at DESC;
```

### View properties with note counts
```sql
SELECT 
    p.id,
    p.name,
    p.address,
    COUNT(n.id) as note_count
FROM properties p
LEFT JOIN notes n ON p.id = n.property_id
GROUP BY p.id, p.name, p.address
ORDER BY note_count DESC;
```

### Find properties near a location (within 10km)
```sql
SELECT 
    name,
    address,
    ST_Distance(
        location,
        ST_GeographyFromText('POINT(-73.985428 40.748817)')
    ) / 1000 as distance_km
FROM properties
WHERE ST_DWithin(
    location,
    ST_GeographyFromText('POINT(-73.985428 40.748817)'),
    10000
)
ORDER BY distance_km;
```

## 🧪 Testing

### Test API with curl

```bash
# Get all properties
curl http://localhost:8080/api/properties.php

# Get specific property
curl http://localhost:8080/api/property.php?id=1

# Add a note
curl -X POST http://localhost:8080/api/add_note.php \
  -H "Content-Type: application/json" \
  -d '{"property_id": 1, "note": "Test note from curl"}'

# Get notes
curl http://localhost:8080/api/notes.php?property_id=1
```

### Test with provided script
```bash
chmod +x scripts/test_api.sh
./scripts/test_api.sh
```

## 📝 Development Workflow

1. **Make changes** to PHP files
2. **Refresh browser** (changes are live via volume mount)
3. **Check logs** if something breaks:
   ```bash
   docker-compose logs -f web
   ```
4. **Database changes** require schema update:
   ```bash
   docker-compose down -v
   docker-compose up -d
   ```

## 🔐 Security Notes

- Never commit `.env` file
- Change default passwords in production
- Use HTTPS in production
- Implement rate limiting for APIs
- Add authentication for sensitive operations

## 📚 Additional Documentation

- `README.md` - Complete setup guide
- `API_DOCUMENTATION.md` - Full API reference
- `ARCHITECTURE_OVERVIEW.md` - Architecture details
- `AI_PROPOSAL.md` - AI enhancement proposal
- `STRUCTURE_NOTES.md` - Project structure explanation

