// mod/subjectattendance/amd/src/attendance.js
define(['jquery'], function($) {

    /**
     * Обновляет CSS-классы селекта в зависимости от значения.
     * @param {HTMLElement} select - элемент select.
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
     * Пересчитывает и обновляет сводку по строке таблицы.
     * @param {HTMLElement} row - строка таблицы (tr).
     */
    function updateRowSummary(row) {
        let present = 0, partial = 0, absent = 0;
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
     * Инициализация обработчиков событий.
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

                fetch(M.cfg.wwwroot + "/mod/subjectattendance/ajax_save.php", {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json",
                        "Accept": "application/json"
                    },
                    body: JSON.stringify({
                        sesskey: M.cfg.sesskey,
                        studentid: studentid,
                        subjectid: subjectid,
                        cmid: cmid,
                        attendanceid: attendanceid,
                        status: $(this).val()
                    })
                })
                .then(r => r.json())
                .then(data => {
                    if (!data.success) {
                        alert(data.error);
                    } else {
                        updateRowSummary($(this).closest("tr"));
                    }
                })
                .catch(error => alert(error));
            });
        });
    }

    return { init: init };
});
