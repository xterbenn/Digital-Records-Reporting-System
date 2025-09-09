<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Appointment - Digital Records and Reporting System</title>
  <style>
    * {
      box-sizing: border-box;
    }

    body {
      margin: 0;
      font-family: Arial, sans-serif;
      background: url('images/landscape.jpg') no-repeat center center fixed;
      background-size: cover;
      position: relative;
    }

    body::before {
      content: '';
      position: fixed;
      top: 0;
      left: 0;
      height: 100%;
      width: 100%;
      background-color: rgba(255, 255, 255, 0.65);
      z-index: -1;
    }

    header {
      background-color: maroon;
      padding: 10px 20px;
      display: flex;
      align-items: center;
      justify-content: space-between;
      color: white;
      position: fixed;
      width: 100%;
      top: 0;
      left: 0;
      z-index: 1000;
    }

    header img {
      height: 50px;
      margin-right: 10px;
    }

    header h1 {
      font-size: 20px;
      margin: 0;
      flex-grow: 1;
    }

    .menu-toggle {
      font-size: 24px;
      cursor: pointer;
      margin-left: 20px;
    }

    .sidebar {
      position: fixed;
      top: 0;
      right: -250px;
      width: 250px;
      height: 100%;
      background-color: #333;
      color: white;
      padding: 60px 20px;
      transition: right 0.3s ease;
      z-index: 999;
    }

    .sidebar.active {
      right: 0;
    }

    .sidebar a {
      display: block;
      color: white;
      text-decoration: none;
      margin: 15px 0;
      border-bottom: 1px solid #555;
      padding-bottom: 5px;
    }

    .container {
      max-width: 600px;
      margin: 100px auto 40px auto;
      background-color: white;
      padding: 25px;
      border-radius: 10px;
      box-shadow: 0 0 10px rgba(0,0,0,0.1);
    }

    h2 {
      text-align: center;
      color: maroon;
      margin-bottom: 20px;
    }

    label {
      display: block;
      margin-top: 15px;
      color: maroon;
      font-weight: bold;
    }

    select, textarea, input {
      width: 100%;
      padding: 10px;
      margin-top: 5px;
      border: 1px solid #ccc;
      border-radius: 5px;
    }

    textarea {
      resize: vertical;
    }

    button {
      margin-top: 20px;
      width: 100%;
      background-color: maroon;
      color: white;
      border: none;
      padding: 12px;
      font-size: 16px;
      border-radius: 5px;
      cursor: pointer;
    }

    button:hover {
      background-color: #800000;
    }

    #confirmationModal {
      display: none;
      position: fixed;
      top: 0; left: 0;
      width: 100%; height: 100%;
      background-color: rgba(0,0,0,0.5);
      justify-content: center;
      align-items: center;
      z-index: 9999;
    }

    #confirmationModal .modal-content {
      background: white;
      padding: 20px 30px;
      border-radius: 10px;
      text-align: center;
      box-shadow: 0 0 10px rgba(0,0,0,0.3);
    }

    @media (max-width: 640px) {
      .container {
        margin: 90px 20px 20px 20px;
        padding: 20px;
      }

      header h1 {
        font-size: 16px;
      }

      .sidebar {
        width: 200px;
      }
    }
  </style>
</head>
<body>

  <header>
    <img src="logo/guidance_office_logo.png" alt="Guidance Office Logo" />
    <h1>Digital Records and Reporting System</h1>
    <div class="menu-toggle" onclick="toggleSidebar()">☰</div>
  </header>

 
  <div class="sidebar" id="sidebar">
    <a href="dashboard.html">Dashboard</a>
    <a href="appointment.html">Book Appointment</a>
    <a href="student_info.html">Student Info</a>
    <a href="schedule.html">Appointment Schedule Availability</a>
    <a href="index.html">Log Out</a>
  </div>

  
  <div class="container">
    <h2>Schedule Appointment</h2>
    <form id="appointmentForm">
      <label for="purpose">Purpose of Visit</label>
      <select id="purpose" name="purpose" required>
        <option value="" disabled selected>Select a purpose</option>
        <option value="Counseling">Counseling</option>
        <option value="Follow-up">Follow-up</option>
        <option value="Consultation">Consultation</option>
        <option value="Others">Others</option>
      </select>

      <label for="schedule">Preferred Schedule</label>
      <input type="date" id="schedule" name="schedule" required />

      <label for="details">Additional Details (optional)</label>
      <textarea id="details" name="details" rows="3"></textarea>

      <button type="submit">Submit</button>
    </form>
  </div>

  
  <div id="confirmationModal">
    <div class="modal-content">
      <h3 style="color: maroon;">✅ Appointment Submitted</h3>
      <p>Your request has been received. Please wait for confirmation.</p>
      <button onclick="closeModal()">OK</button>
    </div>
  </div>

  <!-- jaBAIscript -->
  <script>
    function toggleSidebar() {
      const sidebar = document.getElementById('sidebar');
      sidebar.classList.toggle('active');
    }

    const form = document.getElementById("appointmentForm");
    const modal = document.getElementById("confirmationModal");

    form.addEventListener("submit", function(event) {
      event.preventDefault();
      form.reset();
      modal.style.display = "flex";
    });

    function closeModal() {
      modal.style.display = "none";
    }
  </script>

</body>
</html>
