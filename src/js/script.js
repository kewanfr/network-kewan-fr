let serviceIndex = 0;

function addService() {
  const container = document.getElementById("services-container");
  const html = `
        <fieldset>
          <legend>Service</legend>
          <label>Nom: <input type="text" name="service_nom[${serviceIndex}]"></label><br>
          <label>Port: <input type="number" name="service_port[${serviceIndex}]"></label><br>
          <label>Protocole:
            <select name="service_protocole[${serviceIndex}]">
              <option value="TCP">TCP</option>
              <option value="UDP">UDP</option>
            </select>
          </label><br>
          <div id="domaines-${serviceIndex}"></div>
          <button type="button" onclick="addDomaine(${serviceIndex})">+ Domaine</button>
        </fieldset>
      `;
  container.insertAdjacentHTML("beforeend", html);
  serviceIndex++;
}

function toggleForm() {
  const form = document.getElementById("machine-form");
  form.classList.toggle("hidden");
}

function addDomaine(serviceIdx) {
  const container = document.getElementById(`domaines-${serviceIdx}`);
  const index = container.children.length;
  const html = `
        <div>
          <label>Domaine: <input type="text" name="domaine[${serviceIdx}][${index}]"></label>
          <label><input type="checkbox" name="ssl_enabled[${serviceIdx}][${index}]"> SSL</label>
        </div>
      `;
  container.insertAdjacentHTML("beforeend", html);
}
