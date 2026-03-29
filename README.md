# 🗺️ Tour Guide System (Prototype)

> **🚀 Status:** **STILL WORKING ON IT** (Active Production)
>
> **🎯 Next Major Step:** **🎨 Working on the Landing Page**
>
> **Developer:** YoursTrulyInarius

A premium, modern web platform connecting tourists with expert local guides in the Philippines. The system is transitioning from a traditional PHP-based architecture to a high-performance **React + Vite** frontend.

---

## 🏗️ Technology Stack

Our system leverages a robust, modern stack to ensure performance and scalability:

*   **Frontend Core:** ReactJS (v18+) with Vite for ultra-fast development.
*   **Styling & Design:** Vanilla CSS with HSL-based modern color tokens and **Glassmorphism** effects.
*   **Backend & Logic:** PHP (Vanilla) for heavy-lifting API endpoints and legacy logic.
*   **Database:** MySQL (Relational storage for users, tours, and bookings).
*   **Integrations:** 
    *   **PayPal API:** For secure, global payment processing.
    *   **PHPMailer:** For automated email notifications and booking receipts.

---

## ✨ What We've Changed (Recent Updates)

*   **React Migration:** Transitioned the main user dashboard and booking flows to React for a smoother, single-page experience.
*   **Premium Visuals:** Implemented sophisticated glassmorphism UI/UX across all components.
*   **Secure Checkout:** Fully integrated the **PayPal Checkout Button** for real-time transactions.
*   **Support & Interaction:** Added a persistent **Support Chat** to help tourists in real-time.
*   **Simplified Navigation:** Restructured the app into specialized components (CheckoutForm, SuccessView, LandingPage).

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

