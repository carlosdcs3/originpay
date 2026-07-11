// tests/k6/configs/profiles.js

// Profile options: 'quick', 'standard', 'extreme'
const profile = __ENV.PROFILE || 'quick';

const profiles = {
    quick: {
        // Pull Request / Fast Regression
        // 2-5 minutes
        scenarios: {
            load: {
                executor: 'ramping-arrival-rate',
                preAllocatedVUs: 10,
                maxVUs: 50,
                stages: [
                    { duration: '30s', target: 20 },
                    { duration: '1m', target: 20 },
                    { duration: '30s', target: 0 },
                ],
            }
        }
    },
    standard: {
        // Baseline Generation
        // 15-30 minutes
        scenarios: {
            load: {
                executor: 'ramping-arrival-rate',
                preAllocatedVUs: 50,
                maxVUs: 200,
                stages: [
                    { duration: '2m', target: 50 },
                    { duration: '15m', target: 50 },
                    { duration: '2m', target: 0 },
                ],
            }
        }
    },
    extreme: {
        // Pre Go-Live (Limits discovery)
        // 60+ minutes
        scenarios: {
            load: {
                executor: 'ramping-arrival-rate',
                preAllocatedVUs: 200,
                maxVUs: 1000,
                stages: [
                    { duration: '5m', target: 200 },
                    { duration: '60m', target: 200 },
                    { duration: '5m', target: 0 },
                ],
            }
        }
    },
    soak_24h: {
        scenarios: {
            load: {
                executor: 'constant-arrival-rate',
                rate: 35, // 30-40% of extreme baseline (e.g., 35 req/s)
                timeUnit: '1s',
                duration: '24h',
                preAllocatedVUs: 50,
                maxVUs: 400,
            }
        }
    },
    soak_48h: {
        scenarios: {
            load: {
                executor: 'constant-arrival-rate',
                rate: 55, // 50-60% of baseline
                timeUnit: '1s',
                duration: '48h',
                preAllocatedVUs: 100,
                maxVUs: 600,
            }
        }
    },
    soak_72h: {
        scenarios: {
            load: {
                executor: 'constant-arrival-rate',
                rate: 65, // 60-70% of baseline
                timeUnit: '1s',
                duration: '72h',
                preAllocatedVUs: 150,
                maxVUs: 700,
            }
        }
    }
};

export function getProfile() {
    return profiles[profile] || profiles.quick;
}
