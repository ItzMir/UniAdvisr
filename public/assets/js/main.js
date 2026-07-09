// make getCsrf globally available using window.getCsrf
window.getCsrf = function() {
    // multiple methods to find the CSRF token
    let el = document.querySelector('input[name="csrf_token"]'); // for forms
    if (el && el.value) return el.value;
    el = document.getElementById('global_csrf_token'); // for global use
    if (el && el.value) return el.value;
    el = document.querySelector('meta[name="csrf-token"]'); // for meta tag
    if (el && el.content) return el.content;
    if (window.csrfToken) return window.csrfToken; // fallback to global variable
    console.error('CSRF token not found on page!');
    return '';
};
// Simple function to escape HTML special characters used in user input
function escapeHtml(text) {
    if (!text) return ''; 
    return String(text).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;'); // This replaces dangerous HTML characters(&, <, >) with safe versions
}
//waiting for the page to load before attaching event listeners
document.addEventListener('DOMContentLoaded', () => {

  // ---------- hamburger menu ----------
  const menuBtn = document.getElementById('menuBtn'); //the button that toggles the menu
  const navMenu = document.getElementById('navMenu'); //the menu itself
  if (menuBtn && navMenu) {
    menuBtn.addEventListener('click', () => {
      navMenu.classList.toggle('active'); //toggle the 'active' class to show/hide the menu
      menuBtn.setAttribute('aria-expanded', navMenu.classList.contains('active')); // update the accessibility state so screen readers know whether the menu is open
    });
  }

  // ---------- Profile dropdown ----------
  const profileToggles = document.querySelectorAll('.profile-toggle'); //buttons that open the profile dropdown
  profileToggles.forEach(toggle => { //for each button, add a click event listener
    toggle.addEventListener('click', (e) => {
      e.preventDefault(); //prevent default link behavior if it's an <a> tag
      const dropdown = toggle.nextElementSibling; //the dropdown menu is the next sibling element
      if (dropdown) dropdown.classList.toggle('open'); //toggle the 'open' class to show/hide the dropdown
    });
  });
  document.addEventListener('click', (e) => {
    if (!e.target.closest('.profile-dropdown')) { //if the click is outside any profile dropdown
      document.querySelectorAll('.profile-menu.open').forEach(m => m.classList.remove('open')); //close all open dropdowns
    }
  });

  // ---------- Admin tabs ----------
  //here we are handling tab switching for 2 elements: tab content and tab button status.
  const tabBtns = document.querySelectorAll('.tab-btn');
  const tabs = document.querySelectorAll('.tab-content');
  tabBtns.forEach(btn => {
    btn.addEventListener('click', () => {
      const target = btn.dataset.tab; //get the target tab from the button's data attribute
      tabBtns.forEach(b => b.classList.remove('active')); //remove 'active' class from all buttons
      tabs.forEach(t => t.classList.remove('active')); //remove 'active' class from all tab contents
      btn.classList.add('active'); //add 'active' class to the clicked button
      document.getElementById(target).classList.add('active'); //add 'active' class to the target tab content by showing the tab content that matches the target ID.
    });
  });

  // ---------- Faculty availability popup ----------
  const slotPopup = document.getElementById('slot-popup'); 
  if (slotPopup) {
    const closeBtn = slotPopup.querySelector('.popup-close'); //the button to close the popup
    closeBtn?.addEventListener('click', () => slotPopup.style.display = 'none'); //when the close button is clicked, hide the popup. ?. means it will only add the event listener if the close button exists
    window.addEventListener('click', (e) => { if (e.target === slotPopup) slotPopup.style.display = 'none'; }); //when clicking outside the popup content, hide the popup
    document.querySelectorAll('.btn-view-slots').forEach(btn => { //for each button that shows availability, add a click event listener
      btn.addEventListener('click', async () => { //when the button is clicked, fetch the availability data for that faculty member and show it in the popup
        const facultyId = btn.dataset.faculty;
        const name = btn.dataset.name;
        document.getElementById('popup-faculty-name').textContent = name + ' – Availability'; //set the popup title to include the faculty member's name
        const slotList = document.getElementById('slot-list'); //the element where the availability data will be displayed
        slotList.innerHTML = 'Loading…'; // show a loading message while fetching data
        slotPopup.style.display = 'flex'; //show the popup
        try {
          const res = await fetch(`../api/get_availability.php?faculty_id=${facultyId}`); //fetch the availability data for the selected faculty member
          const data = await res.json(); //parse the response to javaScript object
          let html = ''; //initialize an empty string to build the html content for the availability data
          //if there are availability slots, add them to the html content, data slot can be empty if there are no availability rules set for that faculty member so we check the length too.
          if (data.slots && data.slots.length) {
            html += '<h3>Weekly Schedule</h3>'; //add a header as weekly schedule
            data.slots.forEach(s => html += `<p>${s.day}: ${s.start} – ${s.end}</p>`); //for each availability slot, add a paragraph with the day and time range to the HTML content
          }
          //if there are upcoming unavailability periods, add them to the html content
          if (data.unavailable && data.unavailable.length) {
            html += '<h3 style="margin-top:12px;">Upcoming Unavailability</h3>'; //add a header for upcoming unavailability
            data.unavailable.forEach(u => {
              html += `<p>${u.date} : ${u.start} – ${u.end} ${u.reason ? '('+u.reason+')' : ''}</p>`; //show the unavailable date, time range and reason if exists
            });
          }
          slotList.innerHTML = html || '<p>No availability rules set.</p>'; //if something was found, display it else show no availability rules set
        } catch { slotList.innerHTML = '<p>Failed to load.</p>'; } 
      });
    });
  }

  // ---------- Cancel appointment (student) AJAX ----------
  document.querySelectorAll('.btn-cancel-appt').forEach(btn => {
    btn.addEventListener('click', async () => {
      if (!confirm('Cancel this appointment?')) return; //ask for confirmation before canceling the appointment
      const id = btn.dataset.id; //get the appointment ID from the button's data attribute
      const fd = new FormData(); //create a new form data object to send to the server
      //append the appointment ID, action type and CSRF token to the form data
      fd.append('appointment_id', id);
      fd.append('action', 'cancel_student');
      fd.append('csrf_token', window.getCsrf());
      const res = await fetch('../api/appointment_action.php', { method:'POST', body:fd }); //send a POST request to the server to cancel the appointment
      const data = await res.json(); //read server response as JSON
      //if it worked reload the page to show the changes, else show an error message
      if (data.success) location.reload();
      else alert(data.message || 'Error');
    });
  });

  // ---------- Faculty session actions ----------
  function bindFacultyBtns() {
    //for all start buttons:
    document.querySelectorAll('.btn-start-session').forEach(b => {
      b.removeEventListener('click', b._listener); //remove any existing click listener to prevent duplicate clicks when refreshing the queue
      b._listener = () => sessionAction(b.dataset.id, 'start'); //define a new click listener that starts the session for the given appointment ID
      b.addEventListener('click', b._listener); //add the new click listener to the button
    });
    //for all end buttons:
    document.querySelectorAll('.btn-end-session').forEach(b => {
      b.removeEventListener('click', b._listener); //remove any existing click listener to prevent duplicate clicks when refreshing the queue
      b._listener = () => sessionAction(b.dataset.id, 'complete'); //define a new click listener that completes the session for the given appointment ID
      b.addEventListener('click', b._listener); //add the new click listener to the button
    });
    //for all cancel buttons:
    document.querySelectorAll('.btn-cancel-session').forEach(b => {
      b.removeEventListener('click', b._listener); //remove any existing click listener to prevent duplicate clicks when refreshing the queue
      b._listener = () => sessionAction(b.dataset.id, 'cancel_faculty'); //define a new click listener that cancels the session for the given appointment ID
      b.addEventListener('click', b._listener); //add the new click listener to the button
    });
  }
  // this is the function we used above to handle session actions
  async function sessionAction(apptId, action) {
    if (action === 'cancel_faculty' && !confirm('Cancel this session?')) return;
    
    const csrf = window.getCsrf(); //get CSRF for the request
    if (!csrf) {
      alert('Security token missing. Please refresh the page.');
      return;
    }
    
    const fd = new FormData();
    fd.append('appointment_id', apptId);
    fd.append('action', action);
    fd.append('csrf_token', csrf);
    
    try {
      const res = await fetch('../api/appointment_action.php', { method:'POST', body: fd });
      const data = await res.json();
      if (data.success) location.reload();
      else alert(data.message || 'Action failed');
    } catch (err) {
      console.error('Error:', err);
      alert('Network error. Please try again.');
    }
  }
  
  bindFacultyBtns();

  // ---------- Real‑time queue refresh (faculty dashboard) ----------
  const queueContainer = document.getElementById('queue-container'); //the container that holds the queue cards for the faculty dashboard
  if (queueContainer) {
    const facultyId = queueContainer.dataset.facultyId || (new URLSearchParams(window.location.search)).get('faculty_id') || ''; //get the faculty ID from the container's data attribute or from the URL query parameters
    if (facultyId) {
      setInterval(async () => {
        try {
          const res = await fetch(`../api/token_status.php?faculty_id=${facultyId}`); //fetch the latest queue data for the faculty member
          const data = await res.json();
          let html = ''; //initialize to build the HTML content for the queue cards
          data.forEach(s => {
            html += `
              <div class="queue-card" data-appointment-id="${s.id}">   <!-- create a card for each appointment in the queue, with a data attribute for the appointment ID -->
                <div class="token-badge">${escapeHtml(s.token_code)}</div>   <!-- show the token code safely -->
                <div class="student-info">
                  <h3>${escapeHtml(s.student_name)} (${escapeHtml(s.student_code || 'N/A')})</h3>
                  <p>${escapeHtml(s.topic)}</p>
                  <p>${s.scheduled_start_at} – ${s.scheduled_end_at}</p>
                  <span class="status-${s.status.replace(/_/g,'-')}">${s.status.replace(/_/g,' ')}</span>   <!-- show the status safely -->
                </div>
                <div class="actions">
                  ${s.status === 'approved' ? `<button class="btn-start-session" data-id="${s.id}">Start</button>` : ''}  <!-- if the session is approved, show the start button -->
                  ${s.status === 'in_progress' ? `<button class="btn-end-session" data-id="${s.id}">Complete</button><button class="btn-cancel-session" data-id="${s.id}">Cancel</button>` : ''}  <!-- if the session is in progress, show the complete and cancel buttons -->
                </div>
              </div>`; 
          });
          queueContainer.innerHTML = html;
          bindFacultyBtns();
        } catch(e) { console.error('Queue refresh error', e); }
      }, 30000); //refresh the queue every 30 seconds to show the latest status of appointments without needing a full page reload
    }
  }
});