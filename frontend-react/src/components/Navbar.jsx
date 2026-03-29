import React from 'react';

const Navbar = ({ onLogoClick, onAdminClick }) => {
  return (
    <nav className="navbar">
      <div className="container navbar-content">
        <div className="navbar-brand" onClick={onLogoClick} style={{ cursor: 'pointer' }}>
          <i className="fa-solid fa-map-location-dot"></i>
          <span>YoursTruly Tours</span>
        </div>
        <div className="navbar-actions">
          <button className="btn btn-ghost" onClick={onAdminClick}>
            <i className="fa-solid fa-user-shield"></i>
            <span>Staff Portal</span>
          </button>
        </div>
      </div>
    </nav>
  );
};

export default Navbar;
