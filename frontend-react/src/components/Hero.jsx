const Hero = ({ title, subtitle, imageUrl }) => {
  return (
    <header className="hero">
      <img src={imageUrl || "https://images.unsplash.com/photo-1585320806297-9794b3e4eeae"} alt={title} />
      <div className="hero-content">
        <h1>{title}</h1>
        <p>{subtitle}</p>
      </div>
    </header>
  );
};

export default Hero;
