<!-- GLASSMORPHIC INTERACTIVE WIZARD MODAL (SHARED COMPONENT) -->
<div class="modal-overlay-glass" id="booking-modal-overlay">
  <div class="booking-modal-glass">
    
    <!-- Wizard Title Header -->
    <div class="modal-header-glass">
      <h3 class="modal-title-glass" id="wizard-service-name">Service Booking</h3>
      <button class="modal-close-btn" onclick="closeBookingModal()">&times;</button>
    </div>

    <!-- Booking Form State -->
    <div class="modal-body-glass" id="modal-form-state">
      <form id="service-booking-form" onsubmit="handleBookingSubmit(event)">
        
        <input type="hidden" id="booking-service-title">
        
        <!-- Dynamic Service Type Dropdown -->
        <div class="glass-form-group">
          <label class="glass-form-label" for="booking-sub-type">Choose Option</label>
          <select id="booking-sub-type" class="glass-form-select" required>
            <!-- Options loaded dynamically via JS -->
          </select>
        </div>

        <!-- Schedule Date & Time -->
        <div class="glass-form-row">
          <div class="glass-form-group">
            <label class="glass-form-label" for="booking-date">Select Date</label>
            <input type="date" id="booking-date" class="glass-form-input" required>
          </div>
          <div class="glass-form-group">
            <label class="glass-form-label" for="booking-time">Select Time</label>
            <input type="time" id="booking-time" class="glass-form-input" required>
          </div>
        </div>

        <!-- Customer Name & Phone -->
        <div class="glass-form-row">
          <div class="glass-form-group">
            <label class="glass-form-label" for="booking-name">Full Name</label>
            <input type="text" id="booking-name" class="glass-form-input" placeholder="Your name" required value="Guest User">
          </div>
          <div class="glass-form-group">
            <label class="glass-form-label" for="booking-phone">Phone Number</label>
            <input type="tel" id="booking-phone" class="glass-form-input" placeholder="Your phone number" required value="">
          </div>
        </div>

        <!-- Service Address -->
        <div class="glass-form-group">
          <label class="glass-form-label" for="booking-address">Service Address</label>
          <input type="text" id="booking-address" class="glass-form-input" placeholder="Enter service location / address" required>
        </div>

        <!-- Comments/Additional Requirements -->
        <div class="glass-form-group">
          <label class="glass-form-label" for="booking-notes">Additional Instructions</label>
          <textarea id="booking-notes" class="glass-form-textarea" rows="3" placeholder="Provide details, vehicle type or specific requests..."></textarea>
        </div>

        <!-- Buttons -->
        <div class="modal-footer-glass form-footer">
          <button type="button" class="btn-glass-cancel" onclick="closeBookingModal()">Cancel</button>
          <button type="submit" class="btn-glass-submit">Confirm Booking</button>
        </div>

      </form>
    </div>

    <!-- Success Screen State -->
    <div class="modal-body-glass success-screen-glass" id="modal-success-state">
      <div class="success-icon-wrapper">
        <ion-icon name="checkmark-circle-outline"></ion-icon>
      </div>
      <h4 class="success-title">Booking Confirmed!</h4>
      <p class="success-desc">Your booking request has been successfully generated. A service representative will contact you shortly to confirm arrangements.</p>
      
      <div class="booking-summary-box">
        <div class="summary-row">
          <span class="summary-label">Booking Reference:</span>
          <span class="summary-value" id="res-ref">SE-983274</span>
        </div>
        <div class="summary-row">
          <span class="summary-label">Service Category:</span>
          <span class="summary-value" id="res-service">Vehicle Car &amp; Bike Wash</span>
        </div>
        <div class="summary-row">
          <span class="summary-label">Selected Option:</span>
          <span class="summary-value" id="res-type">Home Wash &amp; Clean</span>
        </div>
        <div class="summary-row">
          <span class="summary-label">Schedule Time:</span>
          <span class="summary-value" id="res-schedule">June 15, 2026 - 10:00 AM</span>
        </div>
      </div>

      <button class="btn-glass-submit full-width" onclick="closeBookingModal()">Finish &amp; Done</button>
    </div>

  </div>
</div>
