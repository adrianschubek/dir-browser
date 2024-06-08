import React, { version } from 'react'

/**
 * Component to display the environment configuration
 * @param {string} name - The name of the environment variable
 * @param {string} init - The initial value of the environment variable
 * @param {string} values - The possible values of the environment variable
 */
const EnvConfig = ({ name, init, values, flags, versions, desc }) => { /* a|b  1|2   1,2|3,4 => a|1|1,2  und .. */
  const configs = [];
  const names = name.split("|");
  const inits = init.split("|");
  const valuess = values.split("|");
  const flagss = flags?.split("|") ?? []; // d = deprecated, u = unreleased, e = experimental
  const versionss = versions?.split("|") ?? []; // 3.1.0,...
  const descs = desc?.split("|") ?? []; // description
  for (let i = 0; i < names.length; i++) {
    configs.push({ name: names[i], init: inits[i], values: valuess[i], flags: flagss[i], versions: versionss[i], desc: descs[i] });
  }

  return (
    <div style={{ padding: "5px", background: "", border: "var(--ifm-color-primary) 2px", borderStyle: "dashed", borderRadius: "5px" }}>
      <h3 style={{ color: "var(--ifm-color-primary)" }}>⚙️ Configuration</h3>
      <table style={{ width: "100%", display: "table", }}>
        <thead>
          <tr>
            <th>Variable</th>
            <th>Default</th>
            <th>Values</th>
            <th>Details</th>
          </tr>
        </thead>
        <tbody>
          {configs.map(({ name, init, values, flags, versions }) => <tr>
            <td>{name}</td>
            <td>{init}</td>
            <td>{values.split(",").map((value, index) => (
              <><span key={index}>{value}</span><br /></>
            ))}</td>
            <td>
              <div style={{
                flexDirection: "column",
                display: "inline-flex",
                width: "100%",
                height: "100%",
                gap: "5px",
              }}>
                {desc !== undefined && <span>{desc}</span>}
                {flags === "u" && <span style={{ borderColor: "var(--ifm-color-primary)", borderWidth: "1px", borderStyle: "solid", borderRadius: "5px", paddingLeft: "5px", paddingRight: "5px", paddingTop: "4px", paddingBottom: "4px", color: "var(--ifm-color-primary)" }}>🔒 <b>Not yet available</b>. This feature will be added in the future.</span>}
                {flags === "d" && <span style={{ borderRadius: "5px", padding: "5px", background: "var(--ifm-color-danger)", color: "white" }}>⚠️ Deprecated</span>}
                {flags === "e" && <span style={{ borderRadius: "5px", padding: "5px", background: "var(--ifm-color-info)", color: "white" }}>🚧 Experimental</span>}
                {versions !== undefined && <span style={{ borderColor: "var(--ifm-color-emphasis-400)", borderWidth: "1px", borderStyle: "solid", borderRadius: "5px", paddingLeft: "5px", paddingRight: "5px", paddingTop: "4px", paddingBottom: "4px", color: "var(--ifm-color-emphasis-600)" }}>added in v{versions}</span>}
              </div>
            </td>
          </tr>)}
        </tbody>
      </table>
      {configs.length > 0 && <details><summary>How to set configuration options</summary>
        Set the environment variables when starting the container.<br></br>Use <code>docker run</code>...
        <ul>
          <li>...with <code>-e {configs[0].name}={configs[0].init}</code></li>
          <li>...with <code>--env-file .env</code> and place <code>{configs[0].name}={configs[0].init}</code> in the file</li>
        </ul>
        See installation page for more details.
      </details>}
    </div>
  )
}

export default EnvConfig