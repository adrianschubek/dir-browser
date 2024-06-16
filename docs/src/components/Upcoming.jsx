const Upcoming = ({ version }) => {
  return (
    <div style={{ borderColor: "rgba(140, 30, 211, 0.8)", borderWidth: "2px", borderStyle: "dashed", background: "rgba(140, 30, 211, 0.3)", borderRadius: "5px", padding: "5px", marginTop: "5px", marginBottom: "5px" }}>
      <span>🚧 <b>Unreleased feature</b> available in upcoming version {version}</span>
    </div>
  );
}

export default Upcoming;