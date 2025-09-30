
        function uploadDavidsonsCSV() {
            const input = document.createElement("input");
            input.type = "file";
            input.accept = ".csv,.xml";
            input.onchange = function(e) {
                const file = e.target.files[0];
                if (file && confirm("Upload " + file.name + " to Davidsons inventory?")) {
                    const formData = new FormData();
                    formData.append("csv_file", file);
                    formData.append("action", "upload_davidsons_csv");
                    formData.append("nonce", fflbro_ajax.nonce);
                    
                    fetch(ajaxurl, {
                        method: "POST",
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        alert(data.success ? "Davidsons Upload: " + data.data.message : "Upload Error: " + data.data);
                        if (data.success) location.reload();
                    });
                }
            };
            input.click();
        }
        
        function viewDavidsonsInventory() {
            fetch(ajaxurl, {
                method: "POST",
                headers: {"Content-Type": "application/x-www-form-urlencoded"},
                body: "action=get_davidsons_inventory&nonce=" + fflbro_ajax.nonce
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert("Davidsons Inventory: " + data.data.count + " products loaded");
                } else {
                    alert("Inventory Error: " + data.data);
                }
            });
        }
