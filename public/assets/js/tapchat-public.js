import { createApp, ref, onMounted, reactive } from 'vue/dist/vue.esm-bundler';
import axios from 'axios';
import DailyIframe from '@daily-co/daily-js';

const app = createApp({
    setup() {
        const tapChatRef = ref('');
        var participants = ref(null);
        var tapcaht = ref(null);
        var section_view_cond = reactive({
                showFtfCard: true,
                requestCall: true,
                onCall: false,
                waiting_over: false,
                waiting: false,
                counter: 0
            }),
            request_form = reactive({
                full_name: tapchat_data.customer.name,
                email: tapchat_data.customer.email,
                phone: ""
            }),
            video_section = reactive({
                PermissionsErrorMsg: false,
                WaitingCard: false,
            }),
            countdown = null,
            ip_address = "https://socket.tapchat.me",
            socket_port = "8080",
            socket;
        var callFrame,
            button_class = "inline-block rounded-full bg-blue-600 text-white leading-normal uppercase shadow-md hover:bg-blue-700 hover:shadow-lg focus:bg-blue-700 focus:shadow-lg focus:outline-none focus:ring-0 active:bg-blue-800 active:shadow-lg transition duration-150 ease-in-out w-9 h-9";
        onMounted(() => {
            socket = io(ip_address, { secure: true });
            socket.on('connect', () => {
                console.log("Lets Call");
            });
        });

        var start_tap_chat_count_down = (counter) => {
            section_view_cond.counter = counter;
            section_view_cond.waiting_over = false;
            countdown = setInterval(() => {
                if (section_view_cond.counter > 0) {
                    section_view_cond.counter--;
                } else {
                    section_view_cond.waiting_over = true;
                    clearInterval(countdown);

                }
            }, 1000);
        };
        // Temporary show loading view while joining the call
        var handleJoiningMeeting = () => {

        };

        var init_call_request = () => {
            tapcaht = open('/tc-voice_text?tc-cid=letscall', 'tapcaht', "width=360,height=600");
            setTimeout(() => {
                tapcaht.document.title = "Tapchat Video Call"; // set title
            }, 100);
        };

        var updateParticpants = async(e) => {
            let data = { action: "tapchat_leave_customer", tapchatn: tapchat_data.tapchatn };
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
                    callFrame.destroy();
                    tapChatRef.value = "";
                    section_view_cond.showFtfCard = false;
                    section_view_cond.requestCall = false;
                    section_view_cond.onCall = false;
                    section_view_cond.waiting_over = false;
                    section_view_cond.waiting = false;
                    request_form.full_name = "";
                    request_form.email = "";
                    request_form.phone = "";
                    tapcaht.close();
                }
            } catch (err) {

            }
        };

        // Show local error in UI when daily-js reports an error
        var handleError = (e) => {
            console.log("[ERROR] ", e);
            error = e.errorMsg;
            loading = false;
        };

        // Show permissions error in UI to alert local participant
        var handleDeviceError = () => {
            video_section.PermissionsErrorMsg = true;
        };

        var get_guest_request_id = (async() => {
            try {
                var form_data = new FormData();
                let data = { action: "tapchat_request", full_name: request_form.full_name, email: request_form.email, phone: request_form.phone, tapchatn: tapchat_data.tapchatn };
                for (var key in data) {
                    form_data.append(key, data[key]);
                }
                const res = await axios.post(tapchat_data.ajax_url,
                    form_data
                );

                if (res.data.status == "error") {
                    alert(res.data.message);
                } else {
                    callFrame = DailyIframe.createFrame(tapChatRef.value, {
                        userName: request_form.full_name,
                        activeSpeakerMode: false,
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

                    socket.on('call_accept' + res.data.caller_req_id, function(msg) {
                        callFrame.join({
                            url: msg.call_url,
                            token: msg.caller_token,
                            showLeaveButton: true,
                        });
                        callFrame.on("joining-meeting", handleJoiningMeeting)
                            .on("participant-left", updateParticpants)
                            .on("left-meeting", updateParticpants)
                            .on("error", handleError)
                            // camera-error = device permissions issue
                            .on("camera-error", handleDeviceError);
                        section_view_cond.onCall = true;
                        section_view_cond.waiting = false;
                    });
                    section_view_cond.requestCall = false;
                    section_view_cond.waiting = true;

                    start_tap_chat_count_down(45);
                }
            } catch (err) {
                alert('unable to process your request.');
                console.log(err);
            }
        });
        return { section_view_cond, init_call_request, tapChatRef, button_class, request_form, video_section, participants, get_guest_request_id, start_tap_chat_count_down }
    }
});
app.mount('#app')