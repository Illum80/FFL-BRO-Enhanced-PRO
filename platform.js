(function() {
    console.log("FFL-BRO Platform loading...");
    
    async function apiCall(endpoint) {
        try {
            const response = await fetch("/wp-json/fflbro/v1/" + endpoint);
            return await response.json();
        } catch (error) {
            console.error("API Error:", error);
            return { status: "error", message: error.message };
        }
    }
    
    function DashboardComponent() {
        const [data, setData] = React.useState(null);
        const [loading, setLoading] = React.useState(true);
        
        React.useEffect(() => {
            apiCall("dashboard").then(response => {
                console.log("Dashboard API response:", response);
                if (response.status === "success") {
                    setData(response.data);
                }
                setLoading(false);
            });
        }, []);
        
        if (loading) return React.createElement("div", { style: { padding: "20px" } }, "Loading dashboard data...");
        if (!data) return React.createElement("div", { style: { padding: "20px", color: "red" } }, "Failed to load dashboard data");
        
        return React.createElement("div", { style: { padding: "20px" } },
            React.createElement("div", { className: "ffl-bro-grid" },
                React.createElement("div", { className: "ffl-bro-kpi ffl-bro-kpi-blue" },
                    React.createElement("h3", { style: { margin: "0 0 10px 0", color: "#1e40af", fontWeight: "bold" } }, "Monthly Revenue"),
                    React.createElement("p", { style: { margin: "0", fontSize: "24px", fontWeight: "bold", color: "#2563eb" } }, 
                        "$" + (data.kpis?.monthly_revenue || 0).toLocaleString())
                ),
                React.createElement("div", { className: "ffl-bro-kpi ffl-bro-kpi-green" },
                    React.createElement("h3", { style: { margin: "0 0 10px 0", color: "#166534", fontWeight: "bold" } }, "Active Quotes"),
                    React.createElement("p", { style: { margin: "0", fontSize: "24px", fontWeight: "bold", color: "#16a34a" } }, 
                        data.kpis?.active_quotes || 0)
                ),
                React.createElement("div", { className: "ffl-bro-kpi ffl-bro-kpi-yellow" },
                    React.createElement("h3", { style: { margin: "0 0 10px 0", color: "#a16207", fontWeight: "bold" } }, "Pending Transfers"),
                    React.createElement("p", { style: { margin: "0", fontSize: "24px", fontWeight: "bold", color: "#ca8a04" } }, 
                        data.kpis?.pending_transfers || 0)
                ),
                React.createElement("div", { className: "ffl-bro-kpi ffl-bro-kpi-purple" },
                    React.createElement("h3", { style: { margin: "0 0 10px 0", color: "#7c2d12", fontWeight: "bold" } }, "Compliance Score"),
                    React.createElement("p", { style: { margin: "0", fontSize: "24px", fontWeight: "bold", color: "#9333ea" } }, 
                        (data.kpis?.compliance_score || 0) + "%")
                )
            )
        );
    }
    
    function QuoteGeneratorComponent() {
        const [quotes, setQuotes] = React.useState([]);
        const [loading, setLoading] = React.useState(true);
        const [showForm, setShowForm] = React.useState(false);
        
        React.useEffect(() => {
            apiCall("quotes").then(response => {
                if (response.status === "success") {
                    setQuotes(response.data.quotes || []);
                }
                setLoading(false);
            });
        }, []);
        
        const handleCreateQuote = (e) => {
            e.preventDefault();
            setShowForm(false);
            // Add quote creation logic here
            alert("Quote creation functionality will be implemented");
        };
        
        if (loading) return React.createElement("div", { style: { padding: "20px" } }, "Loading quotes...");
        
        return React.createElement("div", { style: { padding: "20px" } },
            React.createElement("div", { style: { marginBottom: "20px" } },
                React.createElement("button", { 
                    className: "ffl-bro-btn",
                    onClick: () => setShowForm(!showForm)
                }, showForm ? "Cancel" : "Create New Quote")
            ),
            
            showForm && React.createElement("div", { className: "ffl-bro-card", style: { marginBottom: "20px" } },
                React.createElement("h3", { style: { margin: "0 0 15px 0" } }, "Create New Quote"),
                React.createElement("form", { onSubmit: handleCreateQuote },
                    React.createElement("div", { style: { marginBottom: "10px" } },
                        React.createElement("label", { style: { display: "block", marginBottom: "5px", fontWeight: "bold" } }, "Customer Name"),
                        React.createElement("input", { type: "text", required: true, style: { width: "100%", padding: "8px", border: "1px solid #ccc", borderRadius: "4px" } })
                    ),
                    React.createElement("div", { style: { marginBottom: "10px" } },
                        React.createElement("label", { style: { display: "block", marginBottom: "5px", fontWeight: "bold" } }, "Firearm"),
                        React.createElement("input", { type: "text", required: true, style: { width: "100%", padding: "8px", border: "1px solid #ccc", borderRadius: "4px" } })
                    ),
                    React.createElement("div", { style: { marginBottom: "15px" } },
                        React.createElement("label", { style: { display: "block", marginBottom: "5px", fontWeight: "bold" } }, "Price"),
                        React.createElement("input", { type: "number", required: true, style: { width: "100%", padding: "8px", border: "1px solid #ccc", borderRadius: "4px" } })
                    ),
                    React.createElement("button", { type: "submit", className: "ffl-bro-btn" }, "Create Quote")
                )
            ),
            
            React.createElement("div", { className: "ffl-bro-card" },
                React.createElement("h3", { style: { margin: "0 0 15px 0" } }, "Recent Quotes"),
                quotes.length === 0 ? 
                    React.createElement("p", null, "No quotes found") :
                    React.createElement("div", null,
                        quotes.map((quote, index) =>
                            React.createElement("div", { 
                                key: index, 
                                style: { 
                                    padding: "10px", 
                                    border: "1px solid #e5e7eb", 
                                    borderRadius: "4px", 
                                    marginBottom: "10px",
                                    backgroundColor: "#f9fafb"
                                }
                            },
                                React.createElement("div", { style: { fontWeight: "bold" } }, quote.customer),
                                React.createElement("div", null, "Firearm: " + quote.firearm),
                                React.createElement("div", null, "Price: $" + quote.price),
                                React.createElement("div", { 
                                    style: { 
                                        color: quote.status === "approved" ? "#16a34a" : "#ca8a04",
                                        fontWeight: "bold",
                                        textTransform: "capitalize"
                                    }
                                }, "Status: " + quote.status)
                            )
                        )
                    )
            )
        );
    }
    
    function Form4473Component() {
        const [forms, setForms] = React.useState([]);
        const [loading, setLoading] = React.useState(true);
        
        React.useEffect(() => {
            apiCall("form-4473").then(response => {
                if (response.status === "success") {
                    setForms(response.data.forms || []);
                }
                setLoading(false);
            });
        }, []);
        
        if (loading) return React.createElement("div", { style: { padding: "20px" } }, "Loading Form 4473 data...");
        
        return React.createElement("div", { style: { padding: "20px" } },
            React.createElement("div", { className: "ffl-bro-card" },
                React.createElement("h3", { style: { margin: "0 0 15px 0" } }, "Form 4473 Status"),
                forms.length === 0 ? 
                    React.createElement("p", null, "No forms found") :
                    React.createElement("div", null,
                        forms.map((form, index) =>
                            React.createElement("div", { 
                                key: index, 
                                style: { 
                                    padding: "12px", 
                                    border: "1px solid #e5e7eb", 
                                    borderRadius: "6px", 
                                    marginBottom: "10px",
                                    backgroundColor: "#f9fafb"
                                }
                            },
                                React.createElement("div", { style: { fontWeight: "bold", marginBottom: "5px" } }, 
                                    "Form #" + form.id + " - " + form.customer),
                                React.createElement("div", { style: { marginBottom: "3px" } }, "Firearm: " + form.firearm),
                                React.createElement("div", { style: { marginBottom: "3px" } }, "Date: " + form.date),
                                React.createElement("div", { 
                                    style: { 
                                        color: form.status === "completed" ? "#16a34a" : 
                                               form.status === "pending" ? "#ca8a04" : "#dc2626",
                                        fontWeight: "bold",
                                        textTransform: "capitalize"
                                    }
                                }, "Status: " + form.status)
                            )
                        )
                    )
            )
        );
    }
    
    function MarketResearchComponent() {
        const [data, setData] = React.useState(null);
        const [loading, setLoading] = React.useState(true);
        
        React.useEffect(() => {
            apiCall("market-research").then(response => {
                if (response.status === "success") {
                    setData(response.data);
                }
                setLoading(false);
            });
        }, []);
        
        if (loading) return React.createElement("div", { style: { padding: "20px" } }, "Loading market research...");
        if (!data) return React.createElement("div", { style: { padding: "20px" } }, "No market data available");
        
        return React.createElement("div", { style: { padding: "20px" } },
            React.createElement("h2", { style: { margin: "0 0 20px 0" } }, "Market Opportunities"),
            React.createElement("div", null,
                (data.opportunities || []).map((opp, index) =>
                    React.createElement("div", { 
                        key: index, 
                        className: "ffl-bro-card",
                        style: { 
                            marginBottom: "15px",
                            borderLeft: "4px solid #16a34a"
                        }
                    },
                        React.createElement("h3", { style: { margin: "0 0 10px 0", fontSize: "18px" } }, opp.title),
                        React.createElement("p", { style: { margin: "5px 0", color: "#16a34a", fontWeight: "bold" } }, 
                            "Revenue Potential: $" + (opp.potential_revenue || 0).toLocaleString()),
                        React.createElement("p", { style: { margin: "5px 0", color: "#2563eb" } }, 
                            "Confidence: " + (opp.confidence || 0) + "%")
                    )
                )
            )
        );
    }
    
    function ComplianceComponent() {
        const [data, setData] = React.useState(null);
        const [loading, setLoading] = React.useState(true);
        
        React.useEffect(() => {
            apiCall("compliance").then(response => {
                if (response.status === "success") {
                    setData(response.data);
                }
                setLoading(false);
            });
        }, []);
        
        if (loading) return React.createElement("div", { style: { padding: "20px" } }, "Loading compliance data...");
        if (!data) return React.createElement("div", { style: { padding: "20px" } }, "No compliance data available");
        
        return React.createElement("div", { style: { padding: "20px" } },
            React.createElement("div", { 
                className: "ffl-bro-card",
                style: { 
                    textAlign: "center", 
                    marginBottom: "20px",
                    backgroundColor: "#f0fdf4",
                    border: "1px solid #22c55e"
                }
            },
                React.createElement("h3", { style: { margin: "0 0 10px 0", color: "#166534" } }, "Compliance Score"),
                React.createElement("p", { style: { margin: "0", fontSize: "36px", fontWeight: "bold", color: "#16a34a" } }, 
                    (data.compliance_score || 0) + "%")
            ),
            
            data.alerts && data.alerts.length > 0 && React.createElement("div", null,
                React.createElement("h3", { style: { margin: "0 0 15px 0" } }, "Compliance Alerts"),
                data.alerts.map((alert, index) =>
                    React.createElement("div", { 
                        key: index,
                        style: { 
                            padding: "12px", 
                            backgroundColor: "#fefce8", 
                            border: "1px solid #eab308", 
                            borderLeft: "4px solid #eab308",
                            borderRadius: "4px", 
                            marginBottom: "10px"
                        }
                    },
                        React.createElement("div", { style: { display: "flex", justifyContent: "space-between" } },
                            React.createElement("span", { style: { fontWeight: "bold" } }, alert.message),
                            React.createElement("span", { style: { fontSize: "14px", color: "#92400e" } }, "Due: " + alert.due)
                        )
                    )
                )
            )
        );
    }
    
    function MobileOpsComponent() {
        return React.createElement("div", { style: { padding: "20px" } },
            React.createElement("h2", { style: { margin: "0 0 20px 0" } }, "Mobile Operations Framework"),
            React.createElement("div", { className: "ffl-bro-grid" },
                React.createElement("div", { 
                    className: "ffl-bro-card",
                    style: { textAlign: "center", backgroundColor: "#eff6ff", border: "1px solid #3b82f6" }
                },
                    React.createElement("div", { style: { fontSize: "48px", marginBottom: "10px" } }, "📱"),
                    React.createElement("h3", { style: { margin: "0 0 10px 0", color: "#1e40af" } }, "Quick Inventory"),
                    React.createElement("p", { style: { margin: "0", color: "#2563eb" } }, "Mobile-optimized inventory management")
                ),
                React.createElement("div", { 
                    className: "ffl-bro-card",
                    style: { textAlign: "center", backgroundColor: "#f0fdf4", border: "1px solid #22c55e" }
                },
                    React.createElement("div", { style: { fontSize: "48px", marginBottom: "10px" } }, "🔄"),
                    React.createElement("h3", { style: { margin: "0 0 10px 0", color: "#166534" } }, "Transfer Status"),
                    React.createElement("p", { style: { margin: "0", color: "#16a34a" } }, "Real-time transfer tracking")
                )
            )
        );
    }
    
    function mountComponents() {
        console.log("Mounting FFL-BRO components...");
        
        const mounts = [
            { id: "ffl-bro-dashboard-mount", component: DashboardComponent },
            { id: "ffl-bro-quote-generator-mount", component: QuoteGeneratorComponent },
            { id: "ffl-bro-4473-mount", component: Form4473Component },
            { id: "ffl-bro-market-mount", component: MarketResearchComponent },
            { id: "ffl-bro-compliance-mount", component: ComplianceComponent },
            { id: "ffl-bro-mobile-mount", component: MobileOpsComponent }
        ];
        
        mounts.forEach(mount => {
            const element = document.getElementById(mount.id);
            if (element) {
                const root = ReactDOM.createRoot(element);
                root.render(React.createElement(mount.component));
                console.log("Mounted:", mount.id);
            }
        });
    }
    
    if (document.readyState === "loading") {
        document.addEventListener("DOMContentLoaded", mountComponents);
    } else {
        mountComponents();
    }
})();