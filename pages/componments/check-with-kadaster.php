<?php
    if (isset($includeCheckWithKadaster) && $includeCheckWithKadaster == 1) {
        echo "
        <div id='checkWithKadaster' class='fixed inset-0 bg-black bg-opacity-90 flex items-center justify-center z-50 hidden'>
            <div class='w-[90%] bg-white rounded-xl p-5 max-w-md'>
            <h1 class='pb-2'>Controlleren met Kadaster</h1>
                <div class=\"w-full flex  items-center justify-center\">
                    <img src='/images/kadaster.jpg' alt='Holding the Drones Logo' class='max-w-full max-h-72 object-contain'>
                </div>
                <div class=\"w-full flex  items-center justify-between\">
                    <input type='button' value='Sla voor nu over' onclick='confirmKadasterCheck(0)' class='text-black hover:bg-gray-300 rounded-xl w-1/3' style='padding: 10px; cursor: pointer;'>
                    <input type='button' value='Bevestigen' onclick='confirmKadasterCheck(1)' class='text-white bg-blue-500 hover:bg-blue-700 rounded-xl w-1/3' style='padding: 10px; cursor: pointer;'>
                </div>
            </div>
        </div>
        ";
    }
?>