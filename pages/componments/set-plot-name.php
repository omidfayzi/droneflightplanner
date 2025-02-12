<?php
    if($includeSetPlotName == 1) {
        echo "
        <div id='setPlotName' class='fixed inset-0 bg-black bg-opacity-90 flex items-center justify-center z-50 hidden'>
            <div class='w-[90%] bg-white rounded-xl p-5 max-w-md'>
            <h1 class='pb-2'>Naam perceel</h1>
            <input id='plotNameValue' type='text' class='rounded-xl w-full mb-2' style='padding: 10px; background-color: #D9D9D9;'>
            <input type='button' value='Bevestigen' onclick='confirmPlotName()' class='text-white bg-blue-500 hover:bg-blue-700 rounded-xl w-full' style='padding: 10px; cursor: pointer;'>
            </div>
        </div>
        ";
    }
?>