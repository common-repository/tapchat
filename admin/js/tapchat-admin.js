import axios from 'axios';
import DailyIframe from '@daily-co/daily-js';
var room = {},
    callFrame,
    particiapnt,
    req_id,
    socket;
jQuery(document).ready(function() {
    let ip_address = "https://socket.tapchat.me",
        socket_port = "8080";
    socket = io(ip_address, { secure: true });
    jQuery('.acceptCall').click(async function() {
        try {
            req_id = jQuery(this).data('req_id');
            particiapnt = jQuery(this).data('part_id');
            var form_data = new FormData();
            let data = { action: "tapchat_join_operator", participant_id: particiapnt, tapchatn: tapchat_data.tapchatn };
            for (var key in data) {
                form_data.append(key, data[key]);
            }
            const res = await axios.post(tapchat_data.ajax_url,
                form_data
            );
            if (!res.data.status) {
                alert(res.data.error);
            } else {
                room.url = res.data.url;
                room.token = res.data.token;
                document.getElementById('faceWpRef').classList.remove("hidden");
                callFrame = DailyIframe.createFrame(faceWpRef, {
                    userName: tapchat_data.user,
                    iframeStyle: {
                        width: '100%',
                        height: '36rem',
                    },
                    layoutConfig: {
                        grid: {
                            minTilesPerPage: 2, // default: 1, minimum required: 1
                            maxTilesPerPage: 2, // default: 25, maximum allowable: 49
                        },
                    }
                });
                callFrame.join({
                    url: room.url,
                    showLeaveButton: true,
                    token: room.token
                });
                callFrame.on("joining-meeting", handleJoiningMeeting)
                    .on("participant-left", updateParticpants)
                    .on("left-meeting", updateParticpants)
                    .on("error", handleError)
                    // camera-error = device permissions issue
                    .on("camera-error", handleDeviceError);
            }
        } catch (err) {

        }
    });

    jQuery('#tapchat_exclude_pages,#tapchat_include_pages').select2();
});

var handleJoiningMeeting = () => {
    socket.emit('call_accept', { call_url: room.url, caller_req_id: req_id, caller_token: room.token });
}
var updateParticpants = async() => {
    let data = { action: "tapchat_leave_customer", participant_id: particiapnt, tapchatn: tapchat_data.tapchatn };
    var form_data = new FormData();
    for (var key in data) {
        form_data.append(key, data[key]);
    }
    try {
        const res = await axios.post(tapchat_data.ajax_url,
            form_data
        );
        if (res.data.status == "error") {
            alert('Not able to Update status');
        } else {
            callFrame.leave();
            var faceWpRef = document.getElementById('faceWpRef');
            faceWpRef.innerHTML = "";
            faceWpRef.classList.add("hidden");
            jQuery('[data-req_id="' + req_id + '"]').remove();
        }
    } catch (err) {

    }
}