
$(document).ready(function() {
    //originally from http://stackoverflow.com/questions/1307705/jquery-ui-sortable-with-table-and-tr-width/1372954#1372954
    var fixHelperModified = function(e, tr) {
        var $originals = tr.children();
        var $helper = tr.clone();
        $helper.children().each(function(index)
        {
            $(this).width($originals.eq(index).width())
        });
        return $helper;
    };

    $("table.sortable tbody").sortable({
        placeholder: "ui-sortable-placeholder",
        helper: fixHelperModified,
        update: function(event, ui) {
            console.log(ui);
            console.log(event);

            var sibling = ui.item.prev();
            var siblingId = 0;
            if (sibling.length > 0) {
                siblingId = sibling.data().id;
            }
            var updateUrl = $(this).closest("table").data('sort-url');
            var updateData = { id: ui.item.data().id, after: siblingId };
            console.log(updateData);
            $.ajax({
                type: 'POST',
                url: updateUrl,
                data: updateData,
                success: function(data, textStatus, xhr) {
                    console.log(textStatus);
                    console.log(data);
                },
                dataType: 'text'
            });
        }
    });
    //.disableSelection();
});