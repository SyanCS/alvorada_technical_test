# Full-Stack PHP Engineer

This is a standalone, end-to-end mini-project designed to evaluate real-world full-stack skills using the LAMP stack (Linux, Apache, MySQL, PHP) plus JavaScript for frontend mapping.
The project simulates a lightweight property research workflow.

## Project Goal

Build a small working system that allows a user to:
- Enter a property address
- Automatically enrich the address using a public geolocation API
- Store the property in MySQL
- View the details via an API
- Display the property on an interactive map
- Add notes to the property
- Provide a short written proposal describing how AI/LLM automation could enhance the workflow

Everything must exist as one coherent, standalone project.

## Deliverables

Submit a project folder containing:
- index.php – form to add properties
- /api – PHP endpoints
- /public – static assets and map.html
- /sql/schema.sql – database schema
- AI_PROPOSAL.md – short AI/LLM proposal
- README.md – setup + run instructions

No PHP frameworks allowed (vanilla PHP only).
Minimal JS mapping libraries (ArcGIS or Leaflet) are allowed.

## Requirements

### 1. Property Intake + Enrichment (PHP + MySQL)

Create a simple form at index.php with fields:
- Property name
- Address

When submitted:
1. Call a public API, such as:
    - OpenStreetMap Nominatim
    - Google Geocoding
    - U.S. Census geocoder
    - Any open property dataset
2. Extract:
    - Latitude
    - Longitude
    - One additional data point (e.g., confidence score, zoning type, census block)

3. Insert a record into MySQL with the following schema:

    **Table: properties**
    - id (PK)
    - name
    - address
    - latitude
    - longitude
    - extra_field
    - created_at

4. Show a confirmation page with:

    - The saved data
    - A link to “View on Map”

5. Use prepared statements for database interactions.


### 2. Property Details API (PHP)

Create:

GET /api/property.php?id={id}
Returns JSON:
	•	Property details
	•	All notes associated with that property

POST /api/add_note.php
Adds a new note.

Use a second table:

Table: notes
	•	id (PK)
	•	property_id (FK)
	•	note
	•	created_at

### 3. Interactive Map (HTML + JS)

Create the page:

/public/map.html?id={PROPERTY_ID}

It should:
1.	Fetch the property data from /api/property.php
2.	Load a map using ArcGIS JS API or Leaflet
3.	Center the map on the property location
4.	Create a marker
5.	Show a popup containing:
    - Property name
    - Address
    - Notes

### 4. AI/LLM Proposal (Written Only)

Create a file named AI_PROPOSAL.md.

Write up to one page describing:
- A workflow in this system you would enhance with AI
- Which model/tool you’d use (e.g., OpenAI API, LangChain)
- High-level architecture
- Risks and mitigation strategies

Examples:
- Auto-summarizing properties
- Extracting zoning info from text
- Address normalization
- Lead scoring / prioritization

## Suggested Project Structure

    project-root/
      index.php
      README.md
      AI_PROPOSAL.md
      api/
        db.php
        property.php
        add_note.php
      public/
        map.html
      sql/
        schema.sql
