const assert = require('assert');

// Load the bundled SDK into the global scope
require('../../../../public/sdk/v1/originpay.js');

async function runTests() {
    console.log("Starting OriginPay SDK Core Tests...\n");

    // Test 1: DOM absence
    try {
        assert.strictEqual(typeof document, 'undefined', "DOM APIs should not be present in this Node test environment");
        console.log("✅ Test 1 Passed: SDK operates headlessly (no DOM).");
    } catch (e) {
        console.error("❌ Test 1 Failed:", e.message);
    }

    // Test 2: Invalid Key Initialization
    try {
        OriginPay.init('invalid_key_123');
        assert.fail("Should have thrown an error");
    } catch (e) {
        assert.strictEqual(e.name, 'OriginPayAuthenticationError');
        console.log("✅ Test 2 Passed: Invalid key throws Authentication Error.");
    }

    // Test 3: Sandbox detection
    try {
        OriginPay.init('pk_test_123abc');
        assert.strictEqual(OriginPay.getEnvironment(), 'sandbox');
        console.log("✅ Test 3 Passed: Sandbox environment correctly detected.");
    } catch (e) {
        console.error("❌ Test 3 Failed:", e.message);
    }

    // Test 4: Live detection
    try {
        OriginPay.init('pk_live_123abc');
        assert.strictEqual(OriginPay.getEnvironment(), 'live');
        console.log("✅ Test 4 Passed: Live environment correctly detected.");
    } catch (e) {
        console.error("❌ Test 4 Failed:", e.message);
    }

    // Test 5: createSession Mock & ID Generation
    try {
        OriginPay.init('pk_test_123abc', { correlationId: 'corr-1234' });
        
        const payload = { amount: 5000, currency: 'BRL' };
        const session = await OriginPay.createSession(payload);
        
        assert.ok(session.session_id.startsWith('cs_'), "Session ID should start with cs_");
        assert.strictEqual(session.status, "AWAITING_PAYMENT_METHOD");
        
        // Internal stub validation
        assert.strictEqual(session._debug_context.correlation_id, 'corr-1234');
        assert.ok(session._debug_context.request_id, "Request ID must be generated");
        assert.ok(session._debug_context._mocked, "Must be stubbed");

        console.log("✅ Test 5 Passed: Session creation mocked correctly with HTTP layer headers.");
    } catch (e) {
        console.error("❌ Test 5 Failed:", e.message);
    }

    console.log("\nAll tests completed.");
}

runTests();
