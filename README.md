# Tour Guide System (Prototype)

> **Status:** Under Production / Prototype
> **Developer:** Sonjeev Cabardo
> **Phase:** Advanced UI Revamp & Location Discovery Flow 🚀

A premium web-based platform connecting tourists with professional local guides in the Philippines. The system features a unique, high-end dashboard experience for both users and a logical location-first discovery flow.

## ✨ Recent Revamp Highlights

### 🎨 Premium Dashboard Experience
- **Unique Branding:** Distinct visual themes for Tourists (Teal/Cyan) and Guides (Orange/Gold), ensuring a premium feel for both roles.
- **Glassmorphism UI:** Modern, translucent elements with sleek shadows and Inter typography.
- **Real-time Statistics:** Instant overview of bookings (Pending, Confirmed, Total) directly on the dashboard.

### 🧭 Location-First Discovery ("Plan My Trip")
We've introduced a logical 3-step planning flow for tourists:
1. **Choose Destination:** Select from unique, approved locations across the PH.
2. **Find Local Guide:** Discover guides and tours specifically active in that area.
3. **Notify & Book:** Instantly notify the guide and create a booking request.

### 🖼️ Immersive Tour Details
- Full-width hero sections with gradient overlays.
- Sticky booking widgets for an intuitive reservation process.
- Responsive design for seamless browsing on mobile and desktop.

## 📊 System Flow Diagram

```mermaid
graph TD
    %% Use styles for clarity
    classDef user fill:#e1f5fe,stroke:#01579b,stroke-width:2px;
    classDef process fill:#fff3e0,stroke:#e65100,stroke-width:2px;
    classDef db fill:#f1f8e9,stroke:#33691e,stroke-width:2px;

    Tourist[Tourist]:::user
    Guide[Local Guide]:::user
    
    subgraph Frontend [Immersive Frontend]
        T_Dash[Tourist Dashboard]:::process
        Plan[Plan My Trip Wizard]:::process
        Details[Tour Details Page]:::process
        G_Dash[Guide Dashboard]:::process
    end

    subgraph API [Backend API - PHP]
        T_API[tours.php]:::process
        B_API[bookings.php]:::process
        L_API[tours.php?action=get_locations]:::process
    end

    subgraph Database [MySQL Storage]
        DB_Users[(Users Table)]:::db
        DB_Tours[(Tours Table)]:::db
        DB_Bookings[(Bookings Table)]:::db
    end

    %% Flow logic
    Tourist -->|Selects Place| Plan
    Plan -->|Calls| L_API
    L_API -->|SELECT DISTINCT| DB_Tours
    
    Plan -->|Filters Guides| T_API
    T_API -->|SELECT| DB_Tours
    
    Plan -->|Notify/Book| B_API
    B_API -->|INSERT INTO| DB_Bookings
    
    DB_Bookings -->|Triggers Notification| G_Dash
    Guide -->|Views Request| G_Dash
    G_Dash -->|Update Status| B_API
    B_API -->|UPDATE| DB_Bookings
    
    DB_Bookings -->|Refined Status| T_Dash
```

## 🛠️ Technology Stack

- **Frontend:** HTML5, CSS3 (Vanilla + Modern Tokens), JavaScript (ES6+), AJAX
- **Backend:** PHP (Vanilla)
- **Database:** MySQL
- **Design:** Inter Fonts, HSL-based Palettes, Glassmorphism CSS

## 📥 How to Clone & Run

1.  **Install XAMPP:**
    - Download and install [XAMPP](https://www.apachefriends.org/index.html).
    - Start Apache and MySQL.

2.  **Clone the Repository:**
    ```bash
    cd c:/xampp/htdocs
    git clone <repository_url> Tour_Guide_System
    ```

3.  **Setup the Database:**
    - Go to `http://localhost/phpmyadmin`.
    - Create `tour_guide_db`.
    - Import `backend/database.sql`.

4.  **Run:**
    - Navigate to `http://localhost/Tour_Guide_System`.

## 📝 Usage

1.  **Register/Login:** Choose "Tourist" or "Guide".
2.  **Plan Trip (Tourist):** Use the 🧭 sidebar to find guides by destination.
3.  **Manage (Guide):** Track notifications and booking requests in your dedicated dashboard.
4.  **Explore:** Browse all tours via the 🌍 "Explore" tab.
