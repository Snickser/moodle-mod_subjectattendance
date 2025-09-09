define(['jquery'], function($) {

    /**
     * Updates the CSS classes of a select element depending on its value.
     * @param {HTMLElement} select - the select element.
     */
    function updateClass(select) {
        let val = $(select).val();
        $(select).removeClass("present partial absent none");
        if (val === "2") {
            $(select).addClass("present");
        } else if (val === "1") {
            $(select).addClass("partial");
        } else if (val === "0") {
            $(select).addClass("absent");
        } else {
            $(select).addClass("none");
        }
    }

    /**
     * Recalculates and updates the summary for a table row.
     * @param {HTMLElement} row - the table row (tr).
     */
    function updateRowSummary(row) {
        let present = 0;
        let partial = 0;
        let absent = 0;

        $(row).find(".attendance-select").each(function() {
            if ($(this).val() === "2") {
                present++;
            } else if ($(this).val() === "1") {
                partial++;
            } else if ($(this).val() === "0") {
                absent++;
            }
        });

        let $summary = $(row).find(".attendance-summary");
        if (!$summary.length) {
            return;
        }

        $summary.html(
            (present ? "<div style='flex: 1; background: #c8e6c9;'>" + present + "</div>" : "") +
            (partial ? "<div style='flex: 1; background: #fff9c4;'>" + partial + "</div>" : "") +
            (absent ? "<div style='flex: 1; background: #ffcdd2;'>" + absent + "</div>" : "")
        );
    }

    /**
     * Initializes event handlers.
     */
    function init() {
        $(".attendance-select").each(function() {
            updateClass(this);

            $(this).on("change", function() {
                updateClass(this);

                let studentid = $(this).data("studentid");
                let subjectid = $(this).data("subjectid");
                let cmid = $(this).data("cmid");
                let attendanceid = $(this).data("attendanceid");
                let status = $(this).val();

                fetch(M.cfg.wwwroot + "/mod/subjectattendance/ajax_save.php", {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json",
                        "Accept": "application/json"
                    },
                    body: JSON.stringify({
                        sesskey: M.cfg.sesskey,
                        studentid,
                        subjectid,
                        cmid,
                        attendanceid,
                        status
                    })
                })
                .then(r => r.json())
                .then(data => {
                    if (!data.success) {
                        // eslint-disable-next-line no-alert
                        alert(data.error);
                        return null;
                    } else {
                        updateRowSummary($(this).closest("tr"));
                        return data;
                    }
                })
                .catch(error => {
                    // eslint-disable-next-line no-alert
                    alert(error);
                });
            });
        });

        $(".attendance-col-select").on("change", function() {
            let subjectid = $(this).data("subjectid");
            let value = $(this).val();

            $(".attendance-select[data-subjectid='" + subjectid + "']").each(function() {
                $(this).val(value).trigger("change");
            });
        });
    }

    return {init: init};
});
