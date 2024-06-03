import React from 'react'

/**
 * Component to display the environment configuration
 * @param {string} name - The name of the environment variable
 * @param {string} init - The initial value of the environment variable
 * @param {string} values - The possible values of the environment variable
 */
const EnvConfig = ({ name, init, values }) => { /* a|b  1|2   1,2|3,4 => a|1|1,2  und .. */
  const configs = [];
  const names = name.split("|");
  const inits = init.split("|");
  const valuess = values.split("|");
  for (let i = 0; i < names.length; i++) {
    configs.push({ name: names[i], init: inits[i], values: valuess[i] });
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
          </tr>
        </thead>
        <tbody>
          {configs.map(({ name, init, values }) => <tr>
            <td>{name}</td>
            <td>{init}</td>
            <td>{values.split(",").map((value, index) => (
              <><span key={index}>{value}</span><br /></>
            ))}</td>
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