<?php
// Notification panel opened by the bell in both sidebars. It sits outside <nav> so the
// sidebar's li styles do not apply; JS/home.js positions it next to the sidebar.
if (basename($_SERVER["SCRIPT_FILENAME"]) === basename(__FILE__)) { http_response_code(404); exit; }
?>
    <div id="notif-panel" class="notif-panel hidden" role="dialog" aria-labelledby="notif-title">
      <div class="flex items-start justify-between gap-3 px-2 pb-2 border-b border-slate-100">
        <div>
          <p id="notif-title" class="font-semibold text-slate-900">Notifications</p>
          <p id="notif-count-sub" class="text-xs text-slate-500">You're all caught up</p>
        </div>
        <button type="button" id="notif-close" class="text-slate-400 hover:text-slate-700 text-xl leading-none" aria-label="Close notifications"><i class="bx bx-x"></i></button>
      </div>
      <ul id="notif-list" class="text-sm text-gray-700 overflow-y-auto max-h-[60vh] pt-1">
        <li class="text-gray-400 text-xs text-center py-4">No notifications yet.</li>
      </ul>
    </div>
