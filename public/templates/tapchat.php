<?php wp_head(); ?>
<div id="app" class="fixed bottom-6 right-6">
    <div class="justify-center scroll-smooth flex">
        <div class="block rounded-lg shadow-lg bg-white w-80 max-w-sm text-center">
            <div class="py-3 px-6 border-b border-gray-300">
                <p id="error_message"></p>
                <!--a href="#" class="close-btn" @click="{section_view_cond.showFtfCard=false;section_view_cond.requestCall=false}"><i class="fa fa-times-circle" id="leave_go_live"></i></a -->
            </div>
            <div class="p-6" id="videoFrame">
                <h5 class="text-gray-900 text-xl font-medium mb-2">Welcome</h5>
                <form :class="[section_view_cond.requestCall ? '':'hidden' ]">
                    <div class="form-group mb-6">
                        <input type="text" v-model="request_form.full_name" class="form-control block w-full px-3 py-1.5 text-base font-normal text-gray-700 bg-white bg-clip-padding border border-solid border-gray-300 rounded transition ease-in-out m-0 focus:text-gray-700 focus:bg-white focus:border-blue-600 focus:outline-none" id="FullName" placeholder="Full Name">
                    </div>
                    <div class="form-group mb-6">
                        <input type="email" v-model="request_form.email" class="form-control block w-full px-3 py-1.5 text-base font-normal text-gray-700 bg-white bg-clip-padding border border-solid border-gray-300 rounded transition ease-in-out m-0 focus:text-gray-700 focus:bg-white focus:border-blue-600 focus:outline-none" id="email" placeholder="Email address">
                    </div>
                    <div class="form-group mb-6">
                        <input type="text" v-model="request_form.phone" class="form-control block w-full px-3 py-1.5 text-base font-normal text-gray-700 bg-white bg-clip-padding  border border-solid border-gray-300 rounded transition ease-in-out m-0 focus:text-gray-700 focus:bg-white focus:border-blue-600 focus:outline-none" id="phone" placeholder="Phone">
                    </div>
                    <button type="button" @click="get_guest_request_id" class=" w-full px-6 py-2.5 bg-blue-600 text-white font-medium text-xs leading-tight uppercase rounded shadow-md hover:bg-blue-700 hover:shadow-lg focus:bg-blue-700 focus:shadow-lg focus:outline-none focus:ring-0 active:bg-blue-800 active:shadow-lg transition duration-150 ease-in-out">Request TapChat</button>
                </form>
                <div :class="[section_view_cond.onCall ? 'flex':'hidden' ]">
                    <div ref="tapChatRef" class="w-[36rem] h-[34rem]">
                        
                    </div>
                </div>
            </div>
            <div class="py-3 px-6 border-t border-gray-300 text-gray-600">
                <div :class="[section_view_cond.waiting ? '':'hidden' ]">
                    <div v-show="section_view_cond.counter!=0" class="animate-ping p-2 w-24 h-24 ring-1 ring-slate-900/5 shadow-lg rounded-full mx-auto">
                        <span class="text-6xl">{{section_view_cond.counter}}</span>
                    </div>
                    <div :class="[section_view_cond.waiting_over ? '':'hidden' ]">
                        <input type="button" @click="start_tap_chat_count_down(15)" Value="Keep waiting">
                        <p>OR</p>
                        <input type="button" id="go_live_enquiry_btn" Value="Send Enquiry">
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<script>
window.addEventListener("beforeunload", function(event) {
  event.returnValue = "Are you sure want to leave call.";
});
</script>
<?php wp_footer(); die(); ?>