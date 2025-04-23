<style>
/* Wizard Styles */
.policy-wizard {
    background: white;
    border-radius: 10px;
    box-shadow: 0 5px 15px rgba(0, 104, 55, 0.1);
    overflow: hidden;
    margin: 2rem 0;
}

.wizard-header {
    background: #006837;
    color: white;
    padding: 1.5rem;
    text-align: center;
}

.wizard-header h3 {
    margin: 0;
    font-size: 1.5rem;
}

.wizard-steps {
    padding: 2rem;
}

.wizard-step {
    display: none;
    animation: fadeIn 0.5s ease;
}

.wizard-step.active {
    display: block;
}

.wizard-footer {
    display: flex;
    justify-content: space-between;
    padding: 1.5rem 2rem;
    border-top: 1px solid #e9ecef;
}

.wizard-progress {
    height: 6px;
    background: #e9ecef;
    margin-bottom: 2rem;
    border-radius: 3px;
}

.wizard-progress-bar {
    height: 100%;
    background: #006837;
    border-radius: 3px;
    transition: width 0.5s ease;
}

.step-title {
    color: #006837;
    margin-bottom: 1rem;
    font-size: 1.2rem;
    font-weight: 600;
}

.step-content {
    color: #6c757d;
    line-height: 1.6;
    margin-bottom: 2rem;
}

.step-actions {
    margin-top: 2rem;
}

.btn-wizard {
    padding: 0.8rem 1.5rem;
    border-radius: 8px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s;
}

.btn-next {
    background: #006837;
    color: white;
    border: none;
}

.btn-next:hover {
    background: #004d29;
}

.btn-prev {
    background: white;
    color: #006837;
    border: 2px solid #006837;
}

.btn-prev:hover {
    background: #f8f9fa;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}
</style>

<div class="policy-wizard" id="policyWizard">
    <div class="wizard-header">
        <h3><i class="fas fa-graduation-cap"></i> Policy Education Wizard</h3>
    </div>
    
    <div class="wizard-progress">
        <div class="wizard-progress-bar" id="wizardProgress" style="width: 25%"></div>
    </div>
    
    <div class="wizard-steps">
        <!-- Step 1 -->
        <div class="wizard-step active" data-step="1">
            <h4 class="step-title">What Information We Collect</h4>
            <div class="step-content">
                <p>We collect only what's necessary to provide you with safe pharmaceutical services:</p>
                <ul style="margin-left: 1.5rem; color: #6c757d;">
                    <li>Contact details for order processing</li>
                    <li>Prescription information (as required by law)</li>
                    <li>Device data for security purposes</li>
                    <li>Order history to improve service</li>
                </ul>
            </div>
            <div class="step-actions">
                <button class="btn-next" onclick="nextStep()">Continue <i class="fas fa-arrow-right"></i></button>
            </div>
        </div>
        
        <!-- Step 2 -->
        <div class="wizard-step" data-step="2">
            <h4 class="step-title">How We Use Your Data</h4>
            <div class="step-content">
                <div style="background: #f8f9fa; padding: 1rem; border-radius: 8px; margin-bottom: 1rem;">
                    <i class="fas fa-prescription-bottle-alt" style="color: #006837;"></i>
                    <strong>Primary Uses:</strong>
                    <ul style="margin-left: 1.5rem; margin-top: 0.5rem;">
                        <li>Process medication orders</li>
                        <li>Verify prescriptions with healthcare providers</li>
                        <li>Ensure regulatory compliance</li>
                    </ul>
                </div>
                
                <div style="background: #f8f9fa; padding: 1rem; border-radius: 8px;">
                    <i class="fas fa-chart-line" style="color: #006837;"></i>
                    <strong>Secondary Uses:</strong>
                    <ul style="margin-left: 1.5rem; margin-top: 0.5rem;">
                        <li>Improve our services (anonymous analytics)</li>
                        <li>Send health tips (if opted-in)</li>
                    </ul>
                </div>
            </div>
            <div class="step-actions">
                <button class="btn-prev" onclick="prevStep()"><i class="fas fa-arrow-left"></i> Back</button>
                <button class="btn-next" onclick="nextStep()">Continue <i class="fas fa-arrow-right"></i></button>
            </div>
        </div>
        
        <!-- Step 3 -->
        <div class="wizard-step" data-step="3">
            <h4 class="step-title">Your Rights & Controls</h4>
            <div class="step-content">
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1rem;">
                    <div style="background: #f8f9fa; padding: 1rem; border-radius: 8px;">
                        <i class="fas fa-download" style="color: #006837;"></i>
                        <p><strong>Access Data</strong><br>Request a copy of your information</p>
                    </div>
                    <div style="background: #f8f9fa; padding: 1rem; border-radius: 8px;">
                        <i class="fas fa-edit" style="color: #006837;"></i>
                        <p><strong>Correct Data</strong><br>Update inaccurate information</p>
                    </div>
                    <div style="background: #f8f9fa; padding: 1rem; border-radius: 8px;">
                        <i class="fas fa-ban" style="color: #006837;"></i>
                        <p><strong>Opt-Out</strong><br>Control marketing communications</p>
                    </div>
                    <div style="background: #f8f9fa; padding: 1rem; border-radius: 8px;">
                        <i class="fas fa-trash-alt" style="color: #006837;"></i>
                        <p><strong>Delete Data</strong><br>Request account deletion</p>
                    </div>
                </div>
            </div>
            <div class="step-actions">
                <button class="btn-prev" onclick="prevStep()"><i class="fas fa-arrow-left"></i> Back</button>
                <button class="btn-next" onclick="nextStep()">Continue <i class="fas fa-arrow-right"></i></button>
            </div>
        </div>
        
        <!-- Step 4 -->
        <div class="wizard-step" data-step="4">
            <h4 class="step-title">Returns & Refunds</h4>
            <div class="step-content">
                <div style="background: #fff8e6; padding: 1rem; border-radius: 8px; border-left: 4px solid #FFC107;">
                    <h5 style="margin-top: 0; color: #004d29;"><i class="fas fa-exclamation-circle"></i> Important Policy</h5>
                    <p>Due to pharmaceutical regulations:</p>
                    <ul style="margin-left: 1.5rem;">
                        <li>Prescription medications cannot be returned</li>
                        <li>Other products must be unopened</li>
                        <li>48-hour return window applies</li>
                    </ul>
                </div>
                
                <div style="margin-top: 1.5rem;">
                    <p><strong>Loyalty Members:</strong> Enjoy extended 72-hour return window for eligible products.</p>
                </div>
            </div>
            <div class="step-actions">
                <button class="btn-prev" onclick="prevStep()"><i class="fas fa-arrow-left"></i> Back</button>
                <button class="btn-next" onclick="completeWizard()">Finish <i class="fas fa-check"></i></button>
            </div>
        </div>
        
        <!-- Completion Step -->
        <div class="wizard-step" data-step="5">
            <div style="text-align: center; padding: 2rem 0;">
                <div style="font-size: 4rem; color: #006837; margin-bottom: 1rem;">
                    <i class="fas fa-check-circle"></i>
                </div>
                <h3 class="step-title">Policy Education Complete!</h3>
                <p class="step-content">You now understand how we protect your data and handle returns.</p>
                <div style="margin-top: 2rem;">
                    <button class="btn-next" onclick="closeWizard()" style="padding: 0.8rem 2rem;">
                        <i class="fas fa-thumbs-up"></i> Got It!
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
let currentStep = 1;
const totalSteps = 5;

function updateProgress() {
    const progress = (currentStep / totalSteps) * 100;
    document.getElementById('wizardProgress').style.width = `${progress}%`;
}

function showStep(step) {
    document.querySelectorAll('.wizard-step').forEach(el => {
        el.classList.remove('active');
    });
    document.querySelector(`.wizard-step[data-step="${step}"]`).classList.add('active');
    updateProgress();
}

function nextStep() {
    if(currentStep < totalSteps) {
        currentStep++;
        showStep(currentStep);
    }
}

function prevStep() {
    if(currentStep > 1) {
        currentStep--;
        showStep(currentStep);
    }
}

function completeWizard() {
    // Mark as completed in database
    fetch('complete_wizard.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            user_id: <?= isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 'null' ?>,
            wizard_type: 'policy'
        })
    });
    
    nextStep(); // Show completion screen
}

function closeWizard() {
    document.getElementById('policyWizard').style.display = 'none';
    // Optional: Set a cookie so it doesn't show again for 30 days
    document.cookie = "policy_wizard_completed=true; max-age=" + 60*60*24*30;
}

// Only show wizard if not completed before
if(document.cookie.indexOf('policy_wizard_completed=true') === -1) {
    document.getElementById('policyWizard').style.display = 'block';
}
</script>