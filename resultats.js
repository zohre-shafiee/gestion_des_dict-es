window.onload = function () {

    let rows = document.querySelectorAll("#resultsTable tbody tr");

    rows.forEach(row => {

        let model = row.children[3].innerText;
        let student = row.children[4].innerText;
        let analyseCell = row.children[5];

        fetch("analyse.php", {
            method: "POST",
            headers: {
                "Content-Type": "application/x-www-form-urlencoded"
            },
            body: "model=" + encodeURIComponent(model) +
                  "&student=" + encodeURIComponent(student)
        })
        .then(response => response.text())
        .then(data => {
            analyseCell.innerText = data;
        })
        .catch(() => {
            analyseCell.innerText = "❌ erreur";
        });

    });

};