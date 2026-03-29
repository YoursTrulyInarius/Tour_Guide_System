# 🗺️ Tour Guide System (Prototype)

> **🚀 Status:** **STILL WORKING ON IT** (Active Production)
>
> **🎯 Next Major Step:** **🎞️ Multi-Photo Carousel (Public Booking Page)**
>
> **Developer:** YoursTrulyInarius

A premium, modern web platform connecting tourists with expert local guides in the Philippines. This system features a robust **PHP/MySQL** backend with a high-performance, modern CSS/JS frontend.

---

## 🏗️ Technology Stack

Our system leverages a robust, modern stack to ensure performance and scalability:

*   **⚡ Core Logic:** Native PHP (PHP 7.4/8.0+) with PDO for secure database interactions.
*   **🎨 Design System:** Modern Vanilla CSS with HSL color tokens, Glassmorphism, and responsive Grid/Flex layouts.
*   **🏛️ Database:** MySQL (Stored procedures/relational schema for attractions, tickets, and bookings).
*   **💳 Integrations:** 
    *   **PayPal SDK:** Fully localized for Philippine Peso (PHP) transactions.
    *   **PHPMailer:** Automated HTML email notifications for confirmations and e-receipts.

---

## ✨ What We've Changed (Recent Updates)

*   **🇵🇭 Localization:** Successfully migrated all pricing and payment gateways to Philippine Peso (₱).
*   **🖼️ Admin Redesign:** Replaced legacy list views with a full-width, card-based grid in the Attractions Portal.
*   **🎞️ Multi-Image Support:** Implemented a gallery system allowing up to 3 high-quality uploads per attraction with a built-in JS carousel.
*   **🗺️ Metadata Expansion:** Integrated Lat/Lng coordinates and YouTube video embedding for rich attraction display.
*   **🎟️ Tiered Ticketing:** Added "Adult / Child / Senior" pricing tiers and capacity-managed time slots (Morning/Afternoon).
*   **🚫 Blackout Dates:** Implemented holiday and closure restrictions to prevent invalid bookings.

---

## 🔄 System Process Flow

How the system works for a seamless tourist experience:

1.  **Landing Page Discovery:** Tourists arrive at a high-end landing page to search for destinations.
2.  **Attraction/Guide Selection:** Browse curated tour packages and localized guide profiles.
3.  **Booking Configuration:** Fill out the `CheckoutForm` with tour dates and visitor details.
4.  **Secure Payment:** Trigger the `PayPalButton` to handle payments securely via the PayPal Sandbox/Live API.
5.  **Success & Confirmation:** Automatic redirection to the `SuccessView` and triggered notification to the local guide via backend.
6.  **Support Access:** Ongoing support via the integrated **Support Chat** widget.

---

## 🛠️ Installation & Local Setup

### 1️⃣ XAMPP (Backend & MySQL)
Required to run the PHP backend and database.
*   Download and install [XAMPP](https://www.apachefriends.org/).
*   Start **Apache** and **MySQL**.
*   Import the database from `backend/database_schema.sql` (if available).

### 2️⃣ Node.js (Frontend Development)
Essential for the React environment.
*   **Download:** [Download Node.js](https://nodejs.org/) (Recommended: LTS Version).
*   **Install:** Follow the installer prompts and ensure "Add to PATH" is checked.

### 3️⃣ Running the Project
```bash
# Clone the repository into your xampp/htdocs folder
cd c:/xampp/htdocs
git clone <repository_url> Tour_Guide_System

# Setup Frontend
cd Tour_Guide_System/frontend-react
npm install   # Installs all necessary React dependencies
npm run dev   # Starts the Vite development server
```

---

## 🚧 Current Development Focus

We are currently refining the **Landing Page** to serve as the gateway for the discovery experience. Stay tuned!

