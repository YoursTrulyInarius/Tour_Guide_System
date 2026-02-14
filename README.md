# Tour Guide System (Prototype)

> **Status:** Under Production / Prototype
> **Developer:** Sonjeev Cabardo
> **40%**

A web-based platform connecting tourists with professional local guides in the Philippines. This system allows users to discover, book, and review tours, while guides can manage their tour packages and bookings.

## 🚀 Features

- **For Tourists:**
  - Search and filter tours by destination and price.
  - View detailed tour itineraries and images.
  - Book tours and manage bookings.
  - Leave reviews for completed tours.
- **For Guides:**
  - Create and manage tour packages.
  - View and manage booking requests (Accept/Decline).
  - Upload images for tours.
- **For Admins:**
  - Manage users (Tourists and Guides).
  - Approve new tour packages.
  - Oversee platform activity.

## 🛠️ Technology Stack

- **Frontend:** HTML5, CSS3, JavaScript (ES6+), AJAX
- **Backend:** PHP (Vanilla)
- **Database:** MySQL
- **Environment:** XAMPP (Apache + MySQL)

## 📥 How to Clone & Run

1.  **install XAMPP:**
    - Download and install [XAMPP](https://www.apachefriends.org/index.html).
    - Start Apache and MySQL modules.

2.  **Clone the Repository:**
    - Open your terminal or command prompt.
    - Navigate to the `htdocs` directory:
      ```bash
      cd c:/xampp/htdocs
      ```
    - Clone this repository:
      ```bash
      git clone <repository_url> Tour_Guide_System
      ```

3.  **Setup the Database:**
    - Open your browser and go to `http://localhost/phpmyadmin`.
    - Create a new database named `tour_guide_db`.
    - Import the `backend/database.sql` file into this database.

4.  **Run the Application:**
    - Open your browser and navigate to:
      ```
      http://localhost/Tour_Guide_System
      ```
    - You will be redirected to the landing page.

## 📝 Usage

1.  **Register/Login:** Create an account as a "Tourist" or "Guide".
2.  **Explore (Tourist):** Browse available tours on the landing page or dashboard.
3.  **Create (Guide):** Go to your dashboard to create a new tour package. (Note: New tours require Admin approval unless auto-approved).
4.  **Admin Access:** Login with an admin account to approve tours and manage users.

---
*This system is currently a prototype and is under active development.*
