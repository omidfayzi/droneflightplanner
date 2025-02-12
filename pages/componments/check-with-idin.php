<?php
    if (isset($includeCheckWithIdin) && $includeCheckWithIdin == 1) {
        echo "
        <div id='checkWithIdin' class='fixed inset-0 bg-black bg-opacity-90 flex items-center justify-center z-50 hidden'>
            <div class='w-[90%] bg-white rounded-xl p-5 max-w-md'>
            <h1 class='pb-2'>Controleren met iDIN</h1>
                <div class=\"w-full flex  items-center justify-center h-72\">
                    <img src='/images/idin-logo.svg' alt='Holding the Drones Logo' class='max-w-full max-h-40 object-contain'>
                </div>
                <div class=\"w-full flex  items-center justify-between\">
                    <input type='button' value='Sla over' onclick='confirmIdinCheck(0)' class='text-black hover:bg-gray-300 rounded-xl' style='padding: 10px; cursor: pointer;'>
                    <input type='button' value='Controleer nu' onclick='confirmIdinCheck(1)' class='text-white bg-blue-500 hover:bg-blue-700 rounded-xl' style='padding: 10px; cursor: pointer;'>
                </div>
            </div>
        </div>
        ";
    }
?>