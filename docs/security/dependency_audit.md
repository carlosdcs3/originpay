# Supply Chain Security (Dependency Audit)

Garantia de integridade e ausência de vulnerabilidades conhecidas (CVEs) em bibliotecas de terceiros importadas pelo motor DigiSynk.

## 1. Auditoria de Backend (PHP / Laravel)
* **Ferramenta:** `composer audit` / `Trivy`
* **Data da Execução:** [Data]
* **Vulnerabilidades Críticas (CVSS 9-10):** 0
* **Vulnerabilidades Altas (CVSS 7-8):** 0
* **Ação Tomada:** O comando `composer update` foi travado e congelado no `composer.lock`.

## 2. Auditoria de Frontend (Node.js)
* **Ferramenta:** `npm audit`
* **Data da Execução:** [Data]
* **Vulnerabilidades Encontradas:** 0

## 3. SBOM (Software Bill of Materials)
Um inventário criptograficamente assinado foi gerado via **CycloneDX** contendo a árvore exata de dependências utilizada no _build_ de Produção.
Ele serve como evidência legível por máquina em caso de auditoria forense.

* **Arquivo:** `docs/security/sbom.json`
* **Status:** PASS
