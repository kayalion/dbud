;(function() {
    var curColourIndex = 1, maxColourIndex = 24, nextColour = function() {
        var R,G,B;
        R = parseInt(128+Math.sin((curColourIndex*3+0)*1.3)*128);
        G = parseInt(128+Math.sin((curColourIndex*3+1)*1.3)*128);
        B = parseInt(128+Math.sin((curColourIndex*3+2)*1.3)*128);
        curColourIndex = curColourIndex + 1;
        if (curColourIndex > maxColourIndex) curColourIndex = 1;
        return "rgb(" + R + "," + G + "," + B + ")";
     };

    var targetEndpoint = {
        Endpoint : ["Dot", {radius:2}],
        HoverPaintStyle : {strokeStyle:"#42a62c", lineWidth:2 },
        ConnectionOverlays : [
            [ "Arrow", {
                location:1,
                id:"arrow",
                length:12,
                foldback:0.7
            } ],
            [ "Label", { label:"", id:"label" }]
        ]
    };

    var sourceEndpoint = {
        filter:".ep",
        anchor:"Continuous",
        connector:[ "StateMachine", { curviness:20 } ],
        connectorStyle:{ strokeStyle:nextColour(), lineWidth:2 },
        maxConnections:10,
        onMaxConnections:function(info, e) {
            alert("Maximum connections (" + info.maxConnections + ") reached");
        }
    };

    window.dbudProjectFlow = {
        init :function(saveUrl) {
            jsPlumb.importDefaults(targetEndpoint);

            jsPlumb.draggable($(".w"));

            jsPlumb.bind("click", function(c) {
                jsPlumb.detach(c);
            });

            $(".w").each(function(i,e) {
                jsPlumb.makeSource($(e), sourceEndpoint);
            });

            jsPlumb.bind("beforeDrop", function(info) {
                jsPlumb.detachAllConnections($('#' + info.targetId));

                return true;
            });

            jsPlumb.bind("connection", function(info) {
                info.connection.setPaintStyle({strokeStyle:nextColour()});
                info.connection.getOverlay("label").setLabel('');
            });

            jsPlumb.makeTarget($(".w"), {
                dropOptions:{ hoverClass:"dragHover" },
                anchor:"Continuous"
            });

            //jsPlumb.connect({ source:"opened", target:"phone1" });
            //jsPlumb.connect({ source:"phone1", target:"inperson" });

             $(".r").draggable({revert: true});

            $("#main").droppable({
                drop: function( event, ui ) {
                    if (!ui.draggable.hasClass('r')) {
                        return;
                    }

                    console.log(event);
                    console.log(ui);
                    ui.draggable.draggable('destroy');
                    ui.draggable.removeClass('r');
                    ui.draggable.addClass('w');
                    ui.draggable.addClass('clearfix');
                    $('<div class="ep"></div>').appendTo(ui.draggable);
                    ui.draggable.appendTo(this);
                    jsPlumb.draggable(ui.draggable);

                    jsPlumb.makeSource(ui.draggable, sourceEndpoint);

                    jsPlumb.makeTarget(ui.draggable, {
                        dropOptions:{ hoverClass:"dragHover" },
                        anchor:"Continuous"
                    });
                }
            });

            $('#flow-save').click(function() {
                var objects = [];

                $('.w').each(function() {
                    var previous = null;

                    var connections = jsPlumb.getConnections({scope: '*', source: '*', target: $(this)});
                    for (i = 0; i < connections.length; i++) {
                        if (connections[i].source.attr('id') == $(this).attr('id')) {
                            continue;
                        }

                        previous = connections[i].source.attr('id');
                    }

                    objects[objects.length] = {
                        id: $(this).attr('id'),
                        left: $(this).css('left'),
                        top: $(this).css('top'),
                        previous: previous
                    };
                });

                $.post(saveUrl, {data: objects}).fail(function() {
                    alert('could not save the flow');
                });
            });
        }
    };
})();