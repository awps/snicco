var classes = [
    {
        "name": "Snicco\\Bridge\\SessionPsr16\\Psr16SessionDriver",
        "interface": false,
        "abstract": false,
        "final": true,
        "methods": [
            {
                "name": "__construct",
                "role": null,
                "public": true,
                "private": false,
                "_type": "Hal\\Metric\\FunctionMetric"
            },
            {
                "name": "read",
                "role": null,
                "public": true,
                "private": false,
                "_type": "Hal\\Metric\\FunctionMetric"
            },
            {
                "name": "write",
                "role": null,
                "public": true,
                "private": false,
                "_type": "Hal\\Metric\\FunctionMetric"
            },
            {
                "name": "destroy",
                "role": null,
                "public": true,
                "private": false,
                "_type": "Hal\\Metric\\FunctionMetric"
            },
            {
                "name": "gc",
                "role": null,
                "public": true,
                "private": false,
                "_type": "Hal\\Metric\\FunctionMetric"
            },
            {
                "name": "touch",
                "role": null,
                "public": true,
                "private": false,
                "_type": "Hal\\Metric\\FunctionMetric"
            },
            {
                "name": "readParts",
                "role": null,
                "public": false,
                "private": true,
                "_type": "Hal\\Metric\\FunctionMetric"
            },
            {
                "name": "writeParts",
                "role": null,
                "public": false,
                "private": true,
                "_type": "Hal\\Metric\\FunctionMetric"
            }
        ],
        "nbMethodsIncludingGettersSetters": 8,
        "nbMethods": 8,
        "nbMethodsPrivate": 2,
        "nbMethodsPublic": 6,
        "nbMethodsGetter": 0,
        "nbMethodsSetters": 0,
        "wmc": 25,
        "ccn": 18,
        "ccnMethodMax": 14,
        "externals": [
            "Snicco\\Component\\Session\\Driver\\SessionDriver",
            "Psr\\SimpleCache\\CacheInterface",
            "Snicco\\Component\\Session\\ValueObject\\SerializedSession",
            "Snicco\\Component\\Session\\ValueObject\\SerializedSession",
            "Snicco\\Component\\Session\\ValueObject\\SerializedSession",
            "Snicco\\Component\\Session\\Exception\\CouldNotDestroySessions",
            "Snicco\\Component\\Session\\Exception\\CouldNotDestroySessions",
            "Snicco\\Component\\Session\\Exception\\CouldNotReadSessionContent",
            "Snicco\\Component\\Session\\Exception\\BadSessionID",
            "Snicco\\Component\\Session\\Exception\\CouldNotReadSessionContent",
            "InvalidArgumentException",
            "InvalidArgumentException",
            "InvalidArgumentException",
            "InvalidArgumentException",
            "Snicco\\Component\\Session\\Exception\\CouldNotWriteSessionContent",
            "Snicco\\Component\\Session\\Exception\\CouldNotWriteSessionContent"
        ],
        "parents": [],
        "lcom": 2,
        "length": 155,
        "vocabulary": 30,
        "volume": 760.57,
        "difficulty": 22.73,
        "effort": 17285.64,
        "level": 0.04,
        "bugs": 0.25,
        "time": 960,
        "intelligentContent": 33.46,
        "number_operators": 30,
        "number_operands": 125,
        "number_operators_unique": 8,
        "number_operands_unique": 22,
        "cloc": 12,
        "loc": 91,
        "lloc": 79,
        "mi": 62.68,
        "mIwoC": 36.01,
        "commentWeight": 26.67,
        "kanDefect": 0.71,
        "relativeStructuralComplexity": 196,
        "relativeDataComplexity": 0.26,
        "relativeSystemComplexity": 196.26,
        "totalStructuralComplexity": 1568,
        "totalDataComplexity": 2.07,
        "totalSystemComplexity": 1570.07,
        "package": "Snicco\\Bridge\\SessionPsr16\\",
        "pageRank": 0,
        "afferentCoupling": 2,
        "efferentCoupling": 8,
        "instability": 0.8,
        "violations": {}
    }
]