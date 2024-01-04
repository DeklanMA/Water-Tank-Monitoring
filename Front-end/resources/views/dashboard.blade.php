@extends('layouts.app', ['pageSlug' => 'dashboard'])
<!-- Pastikan jQuery dimuat sebelum script Anda -->
<script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>



<!-- Script AJAX -->
<script>
    function updateSuhu() {
        $.ajax({
            url: 'http://localhost:3000/read_latest_data/ione/suhu',
            method: 'GET',
            dataType: 'json',
            success: function(data) {
                if (data && data['ione/suhu'] !== undefined) {
                    // Parse the value as float and format it with two decimal places
                    var suhuValue = parseFloat(data['ione/suhu']).toFixed(2);
                    $('#suhu').html(
                        '<img src="{{ asset('black') }}/img/suhu.jpg" width="30" height="30" alt="Deskripsi Gambar"></img> ' +
                        suhuValue +
                        ' °C');
                } else {
                    console.error('Format data tidak sesuai atau nilai suhu tidak tersedia.');
                }
            },
            error: function() {
                console.error('Gagal melakukan AJAX request');
            }
        });
    }

    function updatevolumeair() {
        $.ajax({
            url: 'http://localhost:3000/read_latest_data/ione/volumeAir',
            method: 'GET',
            dataType: 'json',
            success: function(data) {
                if (data && data['ione/volumeAir'] !== undefined) {
                    // Parse the value as float and format it with two decimal places
                    var volumeValue = parseFloat(data['ione/volumeAir']).toFixed(2);

                    // Corrected line: Concatenate HTML string with volumeValue
                    $('#volumeair').html('<i class="tim-icons icon-send text-success"></i> ' + volumeValue +
                        ' Liter');
                } else {
                    console.error('Format data tidak sesuai atau nilai suhu tidak tersedia.');
                }
            },
            error: function() {
                console.error('Gagal melakukan AJAX request');
            }
        });
    }


    var pumpStartTime = null; // Variable to store pump start time

    var updatingPompaStatus = false;

    function updatePompaStatus() {
        if (updatingPompaStatus) {
            console.log('Pembaruan sedang berlangsung, lewati.');
            return;
        }

        updatingPompaStatus = true;

        console.log('Mengirim AJAX request...');
        $.ajax({
            url: 'http://localhost:3000/read_latest_data/ione/statuspompa',
            method: 'GET',
            dataType: 'json',
            cache: false,
            timeout: 10000,
            success: function(data) {
                console.log('Berhasil menerima respons dari server.');
                if (data && data['ione/statuspompa'] !== undefined) {
                    var statusPompa = data['ione/statuspompa'];
                    var currentTime = data.currentTime; // Pastikan currentTime ada di respons server
                    console.log('currentTime:', currentTime);

                    if (statusPompa === 1) {
                        pumpStartTime = new Date().getTime();
                        updatePompaDate();
                        updatePompaDuration(currentTime); // Pass currentTime to the function
                        $('#statusPompa').html('<img src="{{ asset('black') }}/img/check_circle.png"> ' +
                            ' ON');
                    } else {
                        resetPompaDuration();
                        $('#statusPompa').html('<img src="{{ asset('black') }}/img/cancel.png"> ' +
                            ' OFF');
                    }
                } else {
                    resetPompaDuration();
                    console.error('Format data tidak sesuai atau status pompa tidak tersedia.');
                }
            },
            error: function(xhr, status, error) {
                resetPompaDuration();
                console.error('Gagal melakukan AJAX request:', status, error);
            },
            complete: function() {
                updatingPompaStatus = false;
            }
        });
    }





    var updateStatusInterval = 30000; // Ubah interval menjadi 30 detik (30,000 milidetik)
    function initiatePompaStatusUpdate() {
        updatePompaStatus();
        setTimeout(initiatePompaStatusUpdate, updateStatusInterval);
    }
    initiatePompaStatusUpdate();






    // Tambahkan fungsi untuk menampilkan tanggal
    function updatePompaDate() {
        var currentDate = new Date();

        // Buat array nama bulan untuk digunakan dalam format
        var monthNames = [
            "Januari", "Februari", "Maret",
            "April", "Mei", "Juni", "Juli",
            "Agustus", "September", "Oktober",
            "November", "Desember"
        ];

        var day = currentDate.getDate();
        var monthIndex = currentDate.getMonth();
        var year = currentDate.getFullYear();

        var formattedDate = day + ' ' + monthNames[monthIndex] + ' ' + year;

        $('#pompaDate').html(formattedDate);
    }




    function updatePompaDuration() {
        if (pumpStartTime) {
            var currentTime = new Date().getTime();
            console.log('currentTime:', currentTime);

            var duration = Math.floor((currentTime - pumpStartTime) / 1000); // in seconds
            console.log('duration:', duration);

            var hours = Math.floor(duration / 3600);
            var minutes = Math.floor((duration % 3600) / 60);
            var seconds = duration % 60;

            var formattedDuration = hours + ' Jam ' + minutes + ' Menit ' + seconds + ' Detik';
            $('#pompaDuration').html('<i class="tim-icons icon-bell-55 text-primary"></i>' + formattedDuration);
        }
    }


    function resetPompaDuration() {
        $('#pompaDuration').html('<i class="tim-icons icon-bell-55 text-primary"></i> ' + '0 Jam 0 Menit');
    }

    // Panggil fungsi updatePompaDuration setiap detik
    setInterval(updatePompaDuration, 1000);


    // Format duration in seconds to 'hh Jam mm Menit' format
    function formatDuration(duration) {
        var hours = Math.floor(duration / 3600);
        var minutes = Math.floor((duration % 3600) / 60);
        var seconds = duration % 60;

        return hours + ' Jam ' + minutes + ' Menit ' + seconds + ' Detik';
    }



    // Use a named function for the setInterval callback
    setInterval(updateSuhu, 1000);
    setInterval(updatevolumeair, 1000);
</script>




@section('content')
    <div class="row">
        <div class="col-12">
            <div class="card card-chart">
                <div class="card-header ">
                    <div class="row">
                        <div class="col-sm-6 text-left">
                            <h5 class="card-category">Kedalaman</h5>
                            <h2 class="card-title">Performance</h2>
                        </div>
                        <div class="col-sm-6">
                            <div class="btn-group btn-group-toggle float-right" data-toggle="buttons">
                                <label class="btn btn-sm btn-primary btn-simple active" id="0">
                                    <input type="radio" name="options" checked>
                                    <span class="d-none d-sm-block d-md-block d-lg-block d-xl-block">Volume</span>
                                    <span class="d-block d-sm-none">
                                        <i class="tim-icons icon-single-02"></i>
                                    </span>
                                </label>
                                <label class="btn btn-sm btn-primary btn-simple" id="1">
                                    <input type="radio" class="d-none d-sm-none" name="options">
                                    <span class="d-none d-sm-block d-md-block d-lg-block d-xl-block">Kedalaman</span>
                                    <span class="d-block d-sm-none">
                                        <i class="tim-icons icon-gift-2"></i>
                                    </span>
                                </label>
                                <label class="btn btn-sm btn-primary btn-simple" id="2">
                                    <input type="radio" class="d-none" name="options">
                                    <span class="d-none d-sm-block d-md-block d-lg-block d-xl-block">Rata-Rata</span>
                                    <span class="d-block d-sm-none">
                                        <i class="tim-icons icon-tap-02"></i>
                                    </span>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="chart-area">
                        <canvas id="chartBig1"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-lg-4">
            <div class="card card-chart">
                <div class="card-header">
                    <h5 class="card-category">Pompa Menyala</h5>
                    <h3 id="pompaDuration" class="card-title"><i class="tim-icons icon-bell-55 text-primary"></i>7 Jam</h3>
                </div>
                <div class="card-body">
                    <div class="chart-area">
                        <canvas id="chartLinePurple"></canvas>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card card-chart">
                <div class="card-header">
                    <h5 class="card-category">SUHU</h5>
                    <h3 id="suhu" class="card-title"> <img src="{{ asset('black') }}/img/suhu.jpg" width="30"
                            height="30" alt="Deskripsi Gambar">
                        30.00 °C</h3>
                </div>
                <div class="card-body">
                    <div class="chart-area">
                        <canvas id="CountryChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card card-chart">
                <div class="card-header">
                    <h5 class="card-category">Volume Air</h5>
                    <h3 id="volumeair" class="card-title"><i class="tim-icons icon-send text-success"></i> 100.00 liter</h3>
                </div>
                <div class="card-body">
                    <div class="chart-area">
                        <canvas id="chartLineGreen"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-lg-6 col-md-12">
            <div class="card ">
                <div class="card-header">
                    <h4 class="card-title">Informasi Pompa</h4>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table tablesorter" id="">
                            <thead class=" text-primary">
                                <tr>
                                    <th>
                                        Name
                                    </th>
                                    <th>
                                        Status
                                    </th>
                                    <th>
                                        Date
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>
                                        Pompa 1
                                    </td>
                                    <td id="statusPompa">
                                        <img src="{{ asset('black') }}/img/check_circle.png"> On
                                    </td>
                                    <td id ="pompaDate">
                                        03 April 2012
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        Pompa 2
                                    </td>
                                    <td>
                                        <img src="{{ asset('black') }}/img/cancel.png"> off
                                    </td>
                                    <td>
                                        06 april 2018
                                    </td>

                                </tr>
                                <tr>
                                    <td>
                                        Pompa 3
                                    </td>
                                    <td>
                                        <img src="{{ asset('black') }}/img/check_circle.png"> On
                                    </td>
                                    <td>
                                        08 april 2020
                                    </td>

                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card card-chart">
                <div class="card-header">
                    <h5 class="card-category">Rata-Rata Water Tank</h5>
                    <h3 id="volumeair" class="card-title"> Info Rata-Rata
                        Water Tank</h3>
                </div>
                <div class="card-body">
                    <div class="chart-area">
                        <canvas id="chartLinePurple1" width="400" height="200"></canvas>
                    </div>
                </div>
            </div>
        </div>

    </div>
@endsection
<script>
    $(document).ready(function() {
        var ctx1 = document.getElementById("chartLinePurple1").getContext("2d");

        var gradientStroke1 = ctx1.createLinearGradient(0, 230, 0, 50);
        gradientStroke1.addColorStop(1, "rgba(72,72,176,0.2)");
        gradientStroke1.addColorStop(0.2, "rgba(72,72,176,0.0)");
        gradientStroke1.addColorStop(0, "rgba(119,52,169,0)"); // purple colors

        var data1 = {
            labels: ["debit_air", "penggunaanLiter", "kapasitas", "kedalaman_air", "suhu_air",
                "stat_pompa"
            ],
            datasets: [{
                label: "Data",
                fill: true,
                backgroundColor: gradientStroke1,
                borderColor: "#d048b6",
                borderWidth: 2,
                borderDash: [],
                borderDashOffset: 0.0,
                pointBackgroundColor: "#d048b6",
                pointBorderColor: "rgba(255,255,255,0)",
                pointHoverBackgroundColor: "#d048b6",
                pointBorderWidth: 20,
                pointHoverRadius: 4,
                pointHoverBorderWidth: 15,
                pointRadius: 4,
                data: [],
            }],
        };

        var myChart1 = new Chart(ctx1, {
            type: 'line',
            data: data1,
            options: {
                scales: {
                    y: {
                        beginAtZero: true
                    }
                },
                responsive: true, // Add this line to make the chart responsive
                maintainAspectRatio: false // Add this line to allow the aspect ratio to be adjusted
            }
        });

        function updateChart1() {
            // Make an AJAX request to your Flask API endpoint to get all average data
            fetch('http://localhost:3000/average_all')
                .then(response => response.json())
                .then(data => {
                    // Update the chart data with the received data
                    myChart1.data.labels = Object.keys(data);
                    myChart1.data.datasets[0].data = Object.values(data);
                    myChart1.update(); // Update the chart to reflect the changes
                })
                .catch(error => console.error('Error fetching data:', error));
        }

        // Initial update when the page loads
        updateChart1();

        // Update the chart every 60 seconds
        setInterval(updateChart1, 600000);

    });
</script>
@push('js')
    <script src="{{ asset('black') }}/js/plugins/chartjs.min.js"></script>
    <script>
        $(document).ready(function() {
            demo.initDashboardPageCharts();
        });
    </script>
@endpush
