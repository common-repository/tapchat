<?php 
global $TapChat;
$data = $TapChat->get_tapchat_requests();
?>
<div id="faceWpRef"
  class="embed-responsive embed-responsive-21by9 relative w-full overflow-hidden hidden"
  style="padding-top: 42.857143%"
>
</div>
<div class="flex flex-col">
  <div class="overflow-x-auto sm:-mx-6 lg:-mx-8">
    <div class="py-2 inline-block min-w-full sm:px-6 lg:px-8">
      <div class="overflow-hidden">
        <table class="min-w-full">
          <thead class="bg-white border-b">
            <tr>
              <th scope="col" class="text-sm font-medium text-gray-900 px-6 py-4 text-left">
                #
              </th>
              <th scope="col" class="text-sm font-medium text-gray-900 px-6 py-4 text-left">
                Full Name
              </th>
              <th scope="col" class="text-sm font-medium text-gray-900 px-6 py-4 text-left">
                Call Status
              </th>
              <th scope="col" class="text-sm font-medium text-gray-900 px-6 py-4 text-left">
                Call Status
              </th>
              <th scope="col" class="text-sm font-medium text-gray-900 px-6 py-4 text-left">
                Action
              </th>
            </tr>
            </thead>
            <tbody>
                <?php if(!empty($data)): 
                    $count = 1;
                    ?>
                    <?php foreach($data as $call): 
                        $class = "bg-gray-100";
                        if($count%2==0):
                            $class = "bg-white";
                        endif;
                    ?>
                    <tr class="<?php esc_attr_e($class); ?> border-b">
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900"><?php esc_attr_e($count); ?></td>
                        <td class="text-sm text-gray-900 font-light px-6 py-4 whitespace-nowrap">
                            <?php esc_html_e($call->full_name); ?>
                        </td>
                        <td class="text-sm text-gray-900 font-light px-6 py-4 whitespace-nowrap">
                            <?php esc_html_e($call->request_status); ?>
                        </td>
                        <td class="text-sm text-gray-900 font-light px-6 py-4 whitespace-nowrap">
                            <?php esc_html_e($call->updated_at); ?>
                        </td>
                        <td>
                            <div>
                                <?php if($call->request_status=="pending"): ?>
                                    <button data-part_id="<?php esc_attr_e($call->customer_id); ?>" data-req_id="<?php esc_attr_e($call->req_id); ?>" class="acceptCall inline-block rounded-full bg-green-600 text-white leading-normal uppercase shadow-md hover:bg-green-700 hover:shadow-lg focus:bg-green-700 focus:shadow-lg focus:outline-none focus:ring-0 active:bg-green-800 active:shadow-lg transition duration-150 ease-in-out w-9 h-9">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6  mx-auto" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                                        </svg>
                                    </button>
                                    <button data-part_id="<?php esc_attr_e($call->customer_id); ?>" data-req_id="<?php esc_attr_e($call->req_id); ?>" class="declineCall inline-block rounded-full bg-red-600 text-white leading-normal uppercase shadow-md hover:bg-red-700 hover:shadow-lg focus:bg-red-700 focus:shadow-lg focus:outline-none focus:ring-0 active:bg-red-800 active:shadow-lg transition duration-150 ease-in-out w-9 h-9">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5  mx-auto" viewBox="0 0 20 20" fill="currentColor">
                                            <path d="M2 3a1 1 0 011-1h2.153a1 1 0 01.986.836l.74 4.435a1 1 0 01-.54 1.06l-1.548.773a11.037 11.037 0 006.105 6.105l.774-1.548a1 1 0 011.059-.54l4.435.74a1 1 0 01.836.986V17a1 1 0 01-1 1h-2C7.82 18 2 12.18 2 5V3z" />
                                            <path d="M16.707 3.293a1 1 0 010 1.414L15.414 6l1.293 1.293a1 1 0 01-1.414 1.414L14 7.414l-1.293 1.293a1 1 0 11-1.414-1.414L12.586 6l-1.293-1.293a1 1 0 011.414-1.414L14 4.586l1.293-1.293a1 1 0 011.414 0z" />
                                        </svg>
                                    </button>
                                <?php endif;?>
                                <?php if($call->request_status=="missed"): ?>
                                    <button data-part_id="<?php esc_attr_e($call->customer_id); ?>" data-req_id="<?php esc_attr_e($call->req_id); ?>" class="responseText inline-block rounded-full bg-blue-600 text-white leading-normal uppercase shadow-md hover:bg-blue-700 hover:shadow-lg focus:bg-blue-700 focus:shadow-lg focus:outline-none focus:ring-0 active:bg-blue-800 active:shadow-lg transition duration-150 ease-in-out w-9 h-9">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6  mx-auto" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z" />
                                        </svg>
                                    </button>
                                <?php endif;?>
                            </div>
                        </td>
                    </tr>
                    <?php $count++;
                    endforeach;
                else: ?>
                <?php endif; ?>
            </tbody>
        </table>
      </div>
    </div>
  </div>
</div>