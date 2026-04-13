<?php
// Doctor Dashboard
// Displays overview, appointments, health records, and prescriptions

session_start();

// Check if user is logged in and is a doctor
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'doctor') {
    header('Location: /auth/login.php');
    exit;
}

$pageTitle = 'Doctor Dashboard';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?> - ClinicEase</title>
    <link rel="stylesheet" href="/css/styles.css">
    <link rel="stylesheet" href="/css/dashboard.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <?php include 'aside.php'; ?>

            <!-- Main Content -->
            <div class="col-md-9 p-4">
                <!-- Header -->
                <?php include 'header.php'; ?>

                <!-- Dashboard Content -->
                <div class="dashboard-content">

                    <!-- Welcome Section -->
                    <div class="welcome-section mb-4">
                        <h1 class="h3 mb-1">Welcome back, Dr. <?php echo htmlspecialchars($_SESSION['last_name'] ?? 'Doctor'); ?></h1>
                        <p class="text-muted">
                            <i class="fas fa-calendar-alt"></i> 
                            <?php echo date('l, F j, Y'); ?>
                        </p>
                    </div>

                    <!-- Quick Stats Section -->
                    <div class="row mb-4">
                        <div class="col-md-3 mb-3">
                            <div class="stat-card bg-primary text-white p-3 rounded">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <p class="mb-0 small">Total Patients</p>
                                        <h3 class="mb-0" id="totalPatients">0</h3>
                                    </div>
                                    <i class="fas fa-users fa-2x opacity-50"></i>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-3 mb-3">
                            <div class="stat-card bg-info text-white p-3 rounded">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <p class="mb-0 small">Today's Appointments</p>
                                        <h3 class="mb-0" id="todayAppointments">0</h3>
                                    </div>
                                    <i class="fas fa-calendar-check fa-2x opacity-50"></i>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-3 mb-3">
                            <div class="stat-card bg-warning text-white p-3 rounded">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <p class="mb-0 small">Pending Records</p>
                                        <h3 class="mb-0" id="pendingRecords">0</h3>
                                    </div>
                                    <i class="fas fa-clipboard-list fa-2x opacity-50"></i>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-3 mb-3">
                            <div class="stat-card bg-success text-white p-3 rounded">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <p class="mb-0 small">Active Prescriptions</p>
                                        <h3 class="mb-0" id="activePrescriptions">0</h3>
                                    </div>
                                    <i class="fas fa-pills fa-2x opacity-50"></i>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Main Content Grid -->
                    <div class="row">
                        <!-- Upcoming Appointments -->
                        <div class="col-md-6 mb-4">
                            <div class="card shadow-sm">
                                <div class="card-header bg-light border-bottom">
                                    <h5 class="mb-0">
                                        <i class="fas fa-calendar-alt text-primary"></i> Upcoming Appointments
                                    </h5>
                                </div>
                                <div class="card-body p-0">
                                    <div id="appointmentsList" class="appointment-list">
                                        <div class="p-3 text-center text-muted">
                                            <p class="mb-0">Loading appointments...</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="card-footer bg-light">
                                    <a href="/appointments.php" class="btn btn-sm btn-outline-primary">
                                        View All Appointments
                                    </a>
                                </div>
                            </div>
                        </div>

                        <!-- Recent Messages -->
                        <div class="col-md-6 mb-4">
                            <div class="card shadow-sm">
                                <div class="card-header bg-light border-bottom">
                                    <h5 class="mb-0">
                                        <i class="fas fa-envelope text-success"></i> Messages
                                    </h5>
                                </div>
                                <div class="card-body p-0">
                                    <div id="messagesList" class="messages-list">
                                        <div class="p-3 text-center text-muted">
                                            <p class="mb-0">Loading messages...</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="card-footer bg-light">
                                    <a href="/messages.php" class="btn btn-sm btn-outline-success">
                                        View All Messages
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Recent Health Records & Prescriptions -->
                    <div class="row">
                        <!-- Recent Health Records -->
                        <div class="col-md-6 mb-4">
                            <div class="card shadow-sm">
                                <div class="card-header bg-light border-bottom">
                                    <h5 class="mb-0">
                                        <i class="fas fa-file-medical text-info"></i> Recent Health Records
                                    </h5>
                                </div>
                                <div class="card-body p-0">
                                    <div id="healthRecordsList" class="records-list">
                                        <div class="p-3 text-center text-muted">
                                            <p class="mb-0">Loading health records...</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="card-footer bg-light">
                                    <a href="/views/doctor/records.php" class="btn btn-sm btn-outline-info">
                                        View All Records
                                    </a>
                                </div>
                            </div>
                        </div>

                        <!-- Recent Prescriptions -->
                        <div class="col-md-6 mb-4">
                            <div class="card shadow-sm">
                                <div class="card-header bg-light border-bottom">
                                    <h5 class="mb-0">
                                        <i class="fas fa-prescription-bottle text-warning"></i> Recent Prescriptions
                                    </h5>
                                </div>
                                <div class="card-body p-0">
                                    <div id="prescriptionsList" class="prescriptions-list">
                                        <div class="p-3 text-center text-muted">
                                            <p class="mb-0">Loading prescriptions...</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="card-footer bg-light">
                                    <a href="/views/doctor/prescriptions.php" class="btn btn-sm btn-outline-warning">
                                        View All Prescriptions
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Quick Actions -->
                    <div class="row mt-4">
                        <div class="col-12">
                            <div class="card shadow-sm border-0">
                                <div class="card-header bg-light border-bottom">
                                    <h5 class="mb-0">
                                        <i class="fas fa-bolt text-danger"></i> Quick Actions
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <div class="row g-3">
                                        <div class="col-md-3 col-sm-6">
                                            <a href="/new-appointment.php" class="btn btn-light w-100 text-start p-3 border">
                                                <i class="fas fa-plus-circle text-primary"></i>
                                                <div class="ms-2 d-inline-block">
                                                    <span class="d-block small fw-bold">Schedule Appointment</span>
                                                    <span class="d-block small text-muted">New patient booking</span>
                                                </div>
                                            </a>
                                        </div>

                                        <div class="col-md-3 col-sm-6">
                                            <a href="/new-health-record.php" class="btn btn-light w-100 text-start p-3 border">
                                                <i class="fas fa-stethoscope text-info"></i>
                                                <div class="ms-2 d-inline-block">
                                                    <span class="d-block small fw-bold">Create Health Record</span>
                                                    <span class="d-block small text-muted">New patient visit</span>
                                                </div>
                                            </a>
                                        </div>

                                        <div class="col-md-3 col-sm-6">
                                            <a href="/new-prescription.php" class="btn btn-light w-100 text-start p-3 border">
                                                <i class="fas fa-prescription text-success"></i>
                                                <div class="ms-2 d-inline-block">
                                                    <span class="d-block small fw-bold">Issue Prescription</span>
                                                    <span class="d-block small text-muted">New medication order</span>
                                                </div>
                                            </a>
                                        </div>

                                        <div class="col-md-3 col-sm-6">
                                            <a href="/view-patients.php" class="btn btn-light w-100 text-start p-3 border">
                                                <i class="fas fa-user-circle text-warning"></i>
                                                <div class="ms-2 d-inline-block">
                                                    <span class="d-block small fw-bold">View My Patients</span>
                                                    <span class="d-block small text-muted">Patient directory</span>
                                                </div>
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Load dashboard data
        document.addEventListener('DOMContentLoaded', function() {
            loadDashboardData();
        });

        function loadDashboardData() {
            // In a real application, this would fetch data from API endpoints
            // For now, we'll use placeholder data

            // Load appointments
            loadAppointments();
            loadHealthRecords();
            loadPrescriptions();
            loadMessages();
            updateStatistics();
        }

        function loadAppointments() {
            const appointmentsList = document.getElementById('appointmentsList');
            // TODO: Fetch from database
            appointmentsList.innerHTML = `
                <div class="appointment-item p-3 border-bottom">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <h6 class="mb-1">John Doe</h6>
                            <small class="text-muted">General Check-up</small>
                            <p class="mb-0 mt-2">
                                <i class="fas fa-clock text-warning"></i>
                                <span class="ms-1">2:30 PM - 3:00 PM</span>
                            </p>
                        </div>
                        <span class="badge bg-success">Confirmed</span>
                    </div>
                </div>
                <div class="appointment-item p-3 border-bottom">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <h6 class="mb-1">Jane Smith</h6>
                            <small class="text-muted">Follow-up Consultation</small>
                            <p class="mb-0 mt-2">
                                <i class="fas fa-clock text-warning"></i>
                                <span class="ms-1">3:30 PM - 4:00 PM</span>
                            </p>
                        </div>
                        <span class="badge bg-warning text-dark">Pending</span>
                    </div>
                </div>
            `;
        }

        function loadHealthRecords() {
            const recordsList = document.getElementById('healthRecordsList');
            // TODO: Fetch from database
            recordsList.innerHTML = `
                <div class="record-item p-3 border-bottom">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <h6 class="mb-1">John Doe - <?php echo date('M d, Y'); ?></h6>
                            <small class="text-muted">General Check-up</small>
                            <p class="mb-0 mt-2">
                                BP: 120/80 | HR: 72 bpm | Temp: 36.8°C
                            </p>
                        </div>
                        <span class="badge bg-success">Final</span>
                    </div>
                </div>
                <div class="record-item p-3">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <h6 class="mb-1">Jane Smith - <?php echo date('M d, Y'); ?></h6>
                            <small class="text-muted">Follow-up</small>
                            <p class="mb-0 mt-2">
                                BP: 118/76 | HR: 68 bpm | Temp: 36.6°C
                            </p>
                        </div>
                        <span class="badge bg-info">Draft</span>
                    </div>
                </div>
            `;
        }

        function loadPrescriptions() {
            const prescriptionsList = document.getElementById('prescriptionsList');
            // TODO: Fetch from database
            prescriptionsList.innerHTML = `
                <div class="prescription-item p-3 border-bottom">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <h6 class="mb-1">Amoxicillin 500mg</h6>
                            <small class="text-muted">John Doe</small>
                            <p class="mb-0 mt-2">
                                <small>Twice daily for 7 days</small>
                            </p>
                        </div>
                        <span class="badge bg-success">Active</span>
                    </div>
                </div>
                <div class="prescription-item p-3">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <h6 class="mb-1">Betamethasone Cream 0.1%</h6>
                            <small class="text-muted">Jane Smith</small>
                            <p class="mb-0 mt-2">
                                <small>Apply topically twice daily</small>
                            </p>
                        </div>
                        <span class="badge bg-success">Active</span>
                    </div>
                </div>
            `;
        }

        function loadMessages() {
            const messagesList = document.getElementById('messagesList');
            // TODO: Fetch from database
            messagesList.innerHTML = `
                <div class="message-item p-3 border-bottom">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <h6 class="mb-1">Patient Inquiry</h6>
                            <small class="text-muted">John Doe</small>
                            <p class="mb-0 mt-1 small">Question about medication side effects...</p>
                        </div>
                        <span class="badge bg-primary">Unread</span>
                    </div>
                </div>
                <div class="message-item p-3">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <h6 class="mb-1">Lab Results Available</h6>
                            <small class="text-muted">System Notification</small>
                            <p class="mb-0 mt-1 small">Patient XYZ has new lab results...</p>
                        </div>
                        <span class="badge bg-secondary">Read</span>
                    </div>
                </div>
            `;
        }

        function updateStatistics() {
            // TODO: Fetch actual data from database
            document.getElementById('totalPatients').textContent = '24';
            document.getElementById('todayAppointments').textContent = '5';
            document.getElementById('pendingRecords').textContent = '3';
            document.getElementById('activePrescriptions').textContent = '12';
        }
    </script>
</body>
</html>
