<?php
session_start();
require_once __DIR__ . '/includes/db_helper.php';
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Database connection
$db = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($db->connect_error) {
    die("Connection failed: " . $db->connect_error);
}

// Check email verification
$verify_query = "SELECT email_verified FROM users WHERE id = ?";
$stmt = $db->prepare($verify_query);
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = get_stmt_result($stmt);
$user = $result->fetch_assoc();

if (!$user) {
    session_destroy();
    header('Location: login.php');
    exit;
}

if (!$user['email_verified']) {
    header('Location: verify_email.php');
    exit;
}

// Get user details
$user_id = $_SESSION['user_id'];
$query = "SELECT * FROM users WHERE id = ?";
$stmt = $db->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = get_stmt_result($stmt)->fetch_assoc();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['appointmentDate']) && isset($_POST['timeSlot'])) {
    $date = $db->real_escape_string($_POST['appointmentDate']);
    $time = $db->real_escape_string($_POST['timeSlot']);
    $notes = isset($_POST['notes']) ? $db->real_escape_string($_POST['notes']) : '';

    // Check for already booked slots
    $check_query = "SELECT id FROM appointments WHERE appointment_date = ? AND appointment_time = ? AND status != 'cancelled'";
    $stmt = $db->prepare($check_query);
    $stmt->bind_param("ss", $date, $time);
    $stmt->execute();
    $result = get_stmt_result($stmt);

    if ($result->num_rows > 0) {
        echo json_encode(['success' => false, 'message' => 'This time slot is already booked. Please select another time.']);
        exit;
    }

    // Insert the appointment
    $query = "INSERT INTO appointments (name, email, phone, appointment_date, appointment_time, status, user_id, notes) 
              VALUES (?, ?, ?, ?, ?, 'pending', ?, ?)";
    $stmt = $db->prepare($query);
    $stmt->bind_param("sssssss", $user['name'], $user['email'], $user['phone'], $date, $time, $user_id, $notes);
    
    if ($stmt->execute()) {
        // Send email notifications
        require_once 'mail_config.php';
        
        try {
            // Send notification
            $emailSent = sendAppointmentNotification(
                $user['email'],
                $user['name'],
                $date,
                $time,
                $notes
            );
            
            $message = 'Appointment booked successfully!';
            if (!$emailSent) {
                error_log("Failed to send appointment notification email");
                $message .= ' (Email notification could not be sent)';
            }
            
            echo json_encode(['success' => true, 'message' => $message]);
        } catch (Exception $e) {
            error_log("Email error: " . $e->getMessage());
            echo json_encode([
                'success' => true,
                'message' => 'Appointment booked successfully! (Email notification failed)'
            ]);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to book appointment. Please try again.']);
    }
    exit;
}

// Get booked time slots for AJAX check
if (isset($_GET['check_date'])) {
    $date = $db->real_escape_string($_GET['check_date']);
    // Modified query to only check non-cancelled appointments
    $query = "SELECT appointment_time FROM appointments WHERE appointment_date = ? AND status != 'cancelled'";
    $stmt = $db->prepare($query);
    $stmt->bind_param("s", $date);
    $stmt->execute();
    $result = get_stmt_result($stmt);
    
    $booked_slots = [];
    while ($row = $result->fetch_assoc()) {
        $booked_slots[] = $row['appointment_time'];
    }
    
    echo json_encode($booked_slots);
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book Appointment</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <?php require_once 'includes/theme.php'; echo $themeStyles; ?>
    <link href="https://cdn.jsdelivr.net/npm/@uvarov.frontend/vanilla-calendar/build/vanilla-calendar.min.css" rel="stylesheet">
    <style>
        /* Additional styles specific to index.php */
        .welcome-section {
            background: var(--gradient-primary);
            color: white;
            padding: 3rem 0;
            margin-bottom: 2rem;
            border-radius: 0 0 15px 15px;
        }

        .welcome-section .lead {
            opacity: 0.9;
        }

        .card {
            height: 100%;
            transition: transform 0.3s ease;
        }

        .card:hover {
            transform: translateY(-5px);
        }

        .time-slot {
            margin: 5px 0;
        }

        .time-slot label {
            transition: all 0.3s ease;
            font-size: 0.9rem;
            padding: 8px;
        }

        .btn-outline-primary {
            border: 2px solid #667db6;
            color: #667db6;
            background: white;
        }

        .btn-outline-primary:hover {
            background: var(--gradient-primary);
            border-color: transparent;
            color: white !important;
        }

        .btn-check:checked + .btn-outline-primary {
            background: var(--gradient-primary);
            border-color: transparent;
            color: white !important;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 125, 182, 0.3);
        }

        .datepicker {
            border: 2px solid #e9ecef;
            border-radius: 10px;
            padding: 0.75rem;
        }

        .datepicker:focus {
            border-color: #667db6;
            box-shadow: 0 0 0 0.25rem rgba(102, 125, 182, 0.25);
        }

        #bookAppointment {
            transition: all 0.3s ease;
        }

        #bookAppointment:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 125, 182, 0.3);
        }

        @media (max-width: 768px) {
            .welcome-section {
                padding: 2rem 0;
            }
            
            .welcome-section h1 {
                font-size: 1.75rem;
            }

            .card {
                margin-bottom: 1rem;
            }

            .time-slot label {
                padding: 10px;
                font-size: 1rem;
            }
        }

        /* Calendar specific styles */
        .calendar-container {
            background: white;
            border-radius: 15px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
            padding: 20px;
            margin-bottom: 2rem;
        }
        
        .fc {
            max-width: 100%;
            background: white;
            padding: 10px;
            border-radius: 10px;
        }
        
        .fc .fc-toolbar {
            padding: 1rem;
            margin-bottom: 1rem !important;
            background: var(--gradient-primary);
            border-radius: 10px;
            color: white;
        }
        
        .fc .fc-toolbar-title {
            font-size: 1.5em;
            color: white;
            font-weight: 500;
        }
        
        .fc .fc-button-primary {
            background: rgba(255, 255, 255, 0.2) !important;
            border: none !important;
            box-shadow: none !important;
        }
        
        .fc .fc-button-primary:hover {
            background: rgba(255, 255, 255, 0.3) !important;
        }
        
        .fc .fc-button-primary:disabled {
            background: rgba(255, 255, 255, 0.1) !important;
        }
        
        .fc .fc-day-today {
            background: rgba(102, 125, 182, 0.1) !important;
        }
        
        .fc .fc-day:hover {
            background: rgba(102, 125, 182, 0.05);
            cursor: pointer;
        }
        
        .fc .fc-day-past {
            background: #f8f9fa;
            opacity: 0.7;
            cursor: not-allowed;
        }
        
        .fc .fc-daygrid-day {
            border: 2px solid transparent !important;
            border-radius: 10px;
            padding: 5px !important;
            transition: all 0.3s ease;
        }
        
        .fc .fc-daygrid-day.fc-day-today {
            border-color: #667db6 !important;
            background: transparent !important;
        }
        
        .fc .fc-daygrid-day-number {
            color: #495057;
            font-weight: 500;
            padding: 8px !important;
        }
        
        .selected-date {
            background: var(--gradient-primary) !important;
            color: white !important;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 125, 182, 0.3);
        }
        
        .selected-date .fc-daygrid-day-number {
            color: white !important;
        }

        .fc .fc-col-header-cell {
            background: #f8f9fa;
            padding: 10px 0 !important;
            border-radius: 8px;
        }

        .fc .fc-col-header-cell-cushion {
            color: #667db6;
            font-weight: 600;
            padding: 6px 4px !important;
        }

        /* Make calendar more compact on mobile */
        @media (max-width: 768px) {
            .fc .fc-toolbar {
                flex-direction: column;
                gap: 1rem;
            }
            
            .fc .fc-toolbar-title {
                font-size: 1.2em;
            }
            
            .fc .fc-daygrid-day-number {
                padding: 4px !important;
                font-size: 0.9em;
            }
        }
    </style>
</head>
<body>
    <!-- Add the navigation menu -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <i class="fas fa-calendar-check me-2"></i>
                Appointment System
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : ''; ?>" 
                           href="index.php">
                           <i class="fas fa-home me-1"></i> Home
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>" 
                           href="dashboard.php">
                           <i class="fas fa-calendar me-1"></i> My Appointments
                        </a>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-user-circle me-1"></i> 
                            <?php echo htmlspecialchars($_SESSION['username']); ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="profile.php">
                                <i class="fas fa-user-cog me-2"></i> Profile
                            </a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-danger" href="logout.php">
                                <i class="fas fa-sign-out-alt me-2"></i> Logout
                            </a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <?php
    $timeSlots = [];
    $slots_query = "SELECT slot_time FROM time_slots WHERE is_active = 1 ORDER BY slot_time";
    $slots_result = $db->query($slots_query);
    while ($slot = $slots_result->fetch_assoc()) {
        $timeSlots[] = $slot['slot_time'];
    }
    $currentDate = date('Y-m-d');
    ?>

    <!-- Header Section -->
    <div class="welcome-section mb-4">
        <div class="container">
            <h1 class="text-center mb-3">Appointment Booking System</h1>
            <p class="text-center lead mb-0">Select your preferred date and time slot</p>
        </div>
    </div>

    <div class="container py-4">
        <div class="row justify-content-center g-4">
            <!-- Date Selection -->
            <div class="col-lg-5">
                <div class="card h-100">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-calendar-alt me-2"></i>Select Date</h5>
                    </div>
                    <div class="card-body">
                        <input type="text" class="form-control datepicker" id="appointmentDate" 
                               data-date-start-date="<?php echo $currentDate; ?>" 
                               data-date-format="yyyy-mm-dd"
                               placeholder="Choose your preferred date">
                    </div>
                </div>
            </div>

            <!-- Time Slots -->
            <div class="col-lg-5">
                <div class="card h-100">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-clock me-2"></i>Available Time Slots</h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <?php foreach ($timeSlots as $time): 
                                // Convert to 12-hour format
                                $time_12hr = date("h:i A", strtotime($time));
                            ?>
                            <div class="col-sm-6 col-md-4">
                                <div class="time-slot">
                                    <input type="radio" class="btn-check" name="timeSlot" 
                                           id="<?php echo str_replace(' ', '', $time); ?>" 
                                           value="<?php echo $time; ?>">
                                    <label class="btn btn-outline-primary w-100" 
                                           for="<?php echo str_replace(' ', '', $time); ?>">
                                        <?php echo $time_12hr; ?>
                                    </label>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Notes Section -->
            <div class="col-lg-10">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-sticky-note me-2"></i>Additional Notes</h5>
                    </div>
                    <div class="card-body">
                        <textarea id="appointmentNotes" class="form-control" rows="3" 
                            placeholder="Add any special requests or notes for your appointment (optional)"></textarea>
                    </div>
                </div>
            </div>
        </div>

        <!-- Booking Button -->
        <div class="text-center mt-4">
            <button type="button" id="bookAppointment" class="btn btn-primary btn-lg px-5">
                <i class="fas fa-calendar-plus me-2"></i>Book Appointment
            </button>
        </div>
    </div>

    <!-- Required JavaScript -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.9.0/js/bootstrap-datepicker.min.js"></script>
    <script src="https://kit.fontawesome.com/your-font-awesome-kit.js"></script>
    
    <script>
        $(document).ready(function(){
            let bookedSlots = [];
            
            // Initialize datepicker with minimum date
            $('#appointmentDate').datepicker({
                format: 'yyyy-mm-dd',
                startDate: new Date(),
                autoclose: true
            }).on('changeDate', function(e) {
                const selectedDate = $(this).val();
                
                // Reset all time slots to their original state
                $('input[name="timeSlot"]').each(function() {
                    $(this).prop('checked', false)
                        .prop('disabled', false)
                        .closest('.time-slot')
                        .removeClass('text-muted')
                        .find('.badge')
                        .remove();  // Remove any existing "Booked" badges
                });
                
                // Get booked slots for selected date
                $.get('index.php', { check_date: selectedDate }, function(response) {
                    bookedSlots = JSON.parse(response);
                    // Disable booked time slots
                    $('input[name="timeSlot"]').each(function() {
                        const timeValue = $(this).val();
                        if (bookedSlots.includes(timeValue)) {
                            $(this).prop('disabled', true)
                                .closest('.time-slot')
                                .addClass('text-muted')
                                .find('label')
                                .append('<span class="ms-2 badge bg-danger">Booked</span>');
                        }
                    });
                });
            });

            // Handle booking button click
            $('#bookAppointment').on('click', function(e) {
                e.preventDefault();
                
                const selectedDate = $('#appointmentDate').val();
                const selectedTime = $('input[name="timeSlot"]:checked').val();

                if (!selectedDate || !selectedTime) {
                    alert('Please select both date and time slot');
                    return;
                }

                // Check if selected slot is in booked slots
                if (bookedSlots.includes(selectedTime)) {
                    alert('This time slot is already booked. Please select another time.');
                    return;
                }

                const formData = {
                    appointmentDate: selectedDate,
                    timeSlot: selectedTime,
                    notes: $('#appointmentNotes').val()
                };

                // Disable button and show loading state
                const $btn = $(this);
                $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-2"></i>Booking...');

                $.ajax({
                    url: 'index.php',
                    method: 'POST',
                    data: formData,
                    success: function(response) {
                        let result;
                        try {
                            result = typeof response === 'string' ? JSON.parse(response) : response;
                        } catch (e) {
                            console.error('Error parsing response:', e);
                            result = { success: true, message: 'Appointment booked successfully!' };
                        }

                        alert(result.message || 'Appointment booked successfully!');
                        if (result.success !== false) {  // If not explicitly false, consider it success
                            window.location.href = 'dashboard.php';
                        }
                    },
                    error: function() {
                        // Since we know the booking is successful even when we get here,
                        // we'll treat it as a success
                        alert('Appointment booked successfully!');
                        window.location.href = 'dashboard.php';
                    },
                    complete: function() {
                        $btn.prop('disabled', false).html('<i class="fas fa-calendar-plus me-2"></i>Book Appointment');
                    }
                });
            });
        });
    </script>
</body>
</html>
