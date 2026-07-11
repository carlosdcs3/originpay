const fs = require('fs');
const path = require('path');

const srcDir = path.join(__dirname, 'src');
const outDir = path.join(__dirname, '../../../public/sdk/v1');
const outFile = path.join(outDir, 'originpay.js');

const errorsSrc = fs.readFileSync(path.join(srcDir, 'errors.js'), 'utf8');
const httpClientSrc = fs.readFileSync(path.join(srcDir, 'http-client.js'), 'utf8');
const sessionSrc = fs.readFileSync(path.join(srcDir, 'session.js'), 'utf8');
const originpaySrc = fs.readFileSync(path.join(srcDir, 'originpay.js'), 'utf8');

// Strip exports/imports for simple concatenation
const stripImportsExports = (code) => {
    return code
        .replace(/export\s+class/g, 'class')
        .replace(/export\s+const/g, 'const')
        .replace(/import\s+.*?from\s+['"].*?['"];?/g, '');
};

const bundle = `
/**
 * OriginPay JS SDK v1
 * Build date: ${new Date().toISOString()}
 * Environment support: Modern Browsers (ES2015+)
 */
(function(global) {
    'use strict';

    // --- errors.js ---
    ${stripImportsExports(errorsSrc)}

    // --- http-client.js ---
    ${stripImportsExports(httpClientSrc)}

    // --- session.js ---
    ${stripImportsExports(sessionSrc)}

    // --- originpay.js ---
    ${stripImportsExports(originpaySrc)}

    // Expose to global window object
    if (typeof window !== 'undefined') {
        window.OriginPay = OriginPay;
    }
    
    // Also expose to global for node tests
    global.OriginPay = OriginPay;

})(typeof window !== 'undefined' ? window : global);
`;

if (!fs.existsSync(outDir)) {
    fs.mkdirSync(outDir, { recursive: true });
}

fs.writeFileSync(outFile, bundle);
console.log('Build completed! File saved to: ' + outFile);
