<?php
// Verify user is logged in
if(!isset($_SESSION['user_id'])){
   header('location:user_login.php');
   exit;
}

// Get user's communication preferences
$user_prefs = $conn->prepare("SELECT receive_marketing FROM users WHERE id = ?");
$user_prefs->execute([$_SESSION['user_id']]);
$prefs = $user_prefs->fetch(PDO::FETCH_ASSOC);
?>

<style>
/* Dashboard Styles - Using Syokichem Color Scheme */
.policy-dashboard {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 2rem;
    margin: 2rem 0;
}

.policy-card {
    background: white;
    border-radius: 10px;
    padding: 2rem;
    box-shadow: 0 5px 15px rgba(0, 104, 55, 0.1);
    border-top: 4px solid #006837;
}

.policy-card h3 {
    color: #006837;
    margin-bottom: 1.5rem;
    display: flex;
    align-items: center;
    gap: 10px;
}

.toggle-group {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.5rem;
}

.switch {
    position: relative;
    display: inline-block;
    width: 50px;
    height: 24px;
}

.switch input {
    opacity: 0;
    width: 0;
    height: 0;
}

.slider {
    position: absolute;
    cursor: pointer;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background-color: #ccc;
    transition: .4s;
    border-radius: 24px;
}

.slider:before {
    position: absolute;
    content: "";
    height: 16px;
    width: 16px;
    left: 4px;
    bottom: 4px;
    background-color: white;
    transition: .4s;
    border-radius: 50%;
}

input:checked + .slider {
    background-color: #006837;
}

input:checked + .slider:before {
    transform: translateX(26px);
}

.process-flow {
    display: flex;
    justify-content: space-between;
    margin-bottom: 2rem;
}

.step {
    text-align: center;
    flex: 1;
    position: relative;
    padding: 10px;
    font-size: 0.9rem;
    color: #6c757d;
}

.step:not(:last-child):after {
    content: "";
    position: absolute;
    top: 50%;
    right: -15px;
    width: 30px;
    height: 2px;
    background: #e9ecef;
}

.step.active {
    color: #006837;
    font-weight: 500;
}

.btn-outline {
    display: inline-block;
    padding: 0.8rem 1.5rem;
    border: 2px solid #006837;
    color: #006837;
    border-radius: 8px;
    font-weight: 600;
    transition: all 0.3s;
}

.btn-outline:hover {
    background: #006837;
    color: white;
}

.btn-primary {
    background: #006837;
    color: white;
    padding: 0.8rem 1.5rem;
    border-radius: 8px;
    font-weight: 600;
    display: block;
    text-align: center;
    transition: all 0.3s;
}

.btn-primary:hover {
    background: #004d29;
    transform: translateY(-2px);
}

@media (max-width: 768px) {
    .policy-dashboard {
        grid-template-columns: 1fr;
    }
    
    .process-flow {
        flex-direction: column;
        gap: 1rem;
    }
    
    .step:not(:last-child):after {
        display: none;
    }
}
</style>

<section class="policy-dashboard">
    <!-- Privacy Center Card -->
    <div class="policy-card">
        <h3><i class="fas fa-shield-alt"></i> Privacy Center</h3>
        <div class="toggle-group">
            <span>Marketing Communications</span>
            <label class="switch">
                <input type="checkbox" id="marketingToggle" <?= ($prefs['receive_marketing'] ?? false) ? 'checked' : '' ?>>
                <span class="slider"></span>
            </label>
        </div>
        <div class="toggle-group">
            <span>Data Collection</span>
            <label class="switch">
                <input type="checkbox" id="dataCollectionToggle" checked disabled>
                <span class="slider"></span>
            </label>
        </div>
        <a href="privacy_policy.php" class="btn-outline">View Full Privacy Policy</a>
    </div>
    
    <!-- Returns Portal Card -->
    <div class="policy-card">
        <h3><i class="fas fa-exchange-alt"></i> Returns Portal</h3>
        <div class="process-flow">
            <div class="step active">1. Report Issue</div>
            <div class="step">2. Get Approval</div>
            <div class="step">3. Return Item</div>
            <div class="step">4. Refund Processed</div>
        </div>
        <button class="btn-primary" id="startReturn">Start Return Request</button>
        <p style="margin-top: 1rem; font-size: 0.9rem; color: #6c757d;">
            <i class="fas fa-info-circle"></i> Prescription medications cannot be returned
        </p>
    </div>
</section>

<script>
// Toggle marketing preferences
document.getElementById('marketingToggle').addEventListener('change', function() {
    const isChecked = this.checked ? 1 : 0;
    
    fetch('update_preferences.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            user_id: <?= $_SESSION['user_id'] ?>,
            preference: 'receive_marketing',
            value: isChecked
        })
    })
    .then(response => response.json())
    .then(data => {
        if(data.success) {
            showNotification('Preferences updated successfully', 'success');
        }
    });
});

// Start return process
document.getElementById('startReturn').addEventListener('click', function() {
    window.location.href = 'returns.php';
});

function showNotification(message, type) {
    // Your existing notification system
}
</script>