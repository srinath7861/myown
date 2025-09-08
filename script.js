// Dashboard JavaScript - Educational Dashboard
// ============================================

// Chart Data Storage
let chartData = {
    weekly: {
        labels: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
        studyHours: [3.5, 4.2, 5.1, 3.8, 4.5, 6.2, 5.8],
        assignments: [2, 3, 1, 4, 2, 1, 3],
        quizScores: [85, 92, 78, 88, 95, 82, 90]
    },
    monthly: {
        labels: ['Week 1', 'Week 2', 'Week 3', 'Week 4'],
        studyHours: [28.5, 32.2, 35.1, 30.8],
        assignments: [12, 15, 11, 14],
        quizScores: [86, 89, 91, 88]
    }
};

let currentChartView = 'weekly';
let chartInstance = null;

// Initialize Dashboard
document.addEventListener('DOMContentLoaded', function() {
    initializeCharts();
    initializeCircularProgress();
    initializeTabSwitching();
    initializeAnimations();
    updateDateTime();
    setInterval(updateDateTime, 60000); // Update every minute
});

// Initialize Performance Chart
function initializeCharts() {
    const canvas = document.getElementById('performanceChart');
    if (!canvas) return;
    
    const ctx = canvas.getContext('2d');
    drawChart(ctx, currentChartView);
}

// Custom Chart Drawing Function
function drawChart(ctx, view) {
    const canvas = ctx.canvas;
    const data = chartData[view];
    
    // Clear canvas
    ctx.clearRect(0, 0, canvas.width, canvas.height);
    
    // Set canvas size
    canvas.width = canvas.offsetWidth;
    canvas.height = canvas.offsetHeight;
    
    const padding = 40;
    const chartWidth = canvas.width - (padding * 2);
    const chartHeight = canvas.height - (padding * 2);
    const barWidth = chartWidth / (data.labels.length * 3 + 1);
    const maxValue = Math.max(
        ...data.studyHours,
        ...data.assignments,
        ...data.quizScores
    );
    
    // Draw grid lines
    ctx.strokeStyle = '#E5E7EB';
    ctx.lineWidth = 1;
    ctx.setLineDash([5, 5]);
    
    for (let i = 0; i <= 5; i++) {
        const y = padding + (chartHeight / 5) * i;
        ctx.beginPath();
        ctx.moveTo(padding, y);
        ctx.lineTo(canvas.width - padding, y);
        ctx.stroke();
    }
    
    ctx.setLineDash([]);
    
    // Draw bars
    data.labels.forEach((label, index) => {
        const x = padding + (index * 3 + 1) * barWidth;
        
        // Study Hours Bar
        const studyHeight = (data.studyHours[index] / maxValue) * chartHeight;
        drawRoundedBar(ctx, x, canvas.height - padding - studyHeight, barWidth * 0.8, studyHeight, '#3A7BFF');
        
        // Assignments Bar
        const assignHeight = (data.assignments[index] / maxValue) * chartHeight;
        drawRoundedBar(ctx, x + barWidth, canvas.height - padding - assignHeight, barWidth * 0.8, assignHeight, '#65D6A6');
        
        // Quiz Scores Bar
        const quizHeight = (data.quizScores[index] / maxValue) * chartHeight;
        drawRoundedBar(ctx, x + barWidth * 2, canvas.height - padding - quizHeight, barWidth * 0.8, quizHeight, '#FFBE3C');
        
        // Draw labels
        ctx.fillStyle = '#6B6B6B';
        ctx.font = '12px sans-serif';
        ctx.textAlign = 'center';
        ctx.fillText(label, x + barWidth * 1.5, canvas.height - padding + 20);
    });
}

// Draw Rounded Bar Helper
function drawRoundedBar(ctx, x, y, width, height, color) {
    const radius = Math.min(width / 2, 4);
    
    ctx.fillStyle = color;
    ctx.beginPath();
    ctx.moveTo(x + radius, y);
    ctx.lineTo(x + width - radius, y);
    ctx.quadraticCurveTo(x + width, y, x + width, y + radius);
    ctx.lineTo(x + width, y + height);
    ctx.lineTo(x, y + height);
    ctx.lineTo(x, y + radius);
    ctx.quadraticCurveTo(x, y, x + radius, y);
    ctx.closePath();
    ctx.fill();
}

// Initialize Circular Progress
function initializeCircularProgress() {
    const progressElements = document.querySelectorAll('.circular-progress');
    
    progressElements.forEach(element => {
        const progress = parseInt(element.dataset.progress);
        const circle = element.querySelector('.progress-fill');
        
        if (circle) {
            const circumference = 2 * Math.PI * 45;
            const offset = circumference - (progress / 100) * circumference;
            
            circle.style.strokeDasharray = circumference;
            circle.style.strokeDashoffset = circumference;
            
            // Animate after a short delay
            setTimeout(() => {
                circle.style.strokeDashoffset = offset;
            }, 100);
        }
    });
}

// Tab Switching for Charts
function initializeTabSwitching() {
    const tabButtons = document.querySelectorAll('.tab-btn');
    
    tabButtons.forEach(button => {
        button.addEventListener('click', function() {
            // Remove active class from all tabs
            tabButtons.forEach(btn => btn.classList.remove('active'));
            
            // Add active class to clicked tab
            this.classList.add('active');
            
            // Update chart based on selected view
            currentChartView = this.dataset.chart;
            const canvas = document.getElementById('performanceChart');
            if (canvas) {
                const ctx = canvas.getContext('2d');
                drawChart(ctx, currentChartView);
            }
        });
    });
}

// Initialize Animations
function initializeAnimations() {
    // Animate cards on scroll
    const observerOptions = {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    };
    
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.style.opacity = '1';
                entry.target.style.transform = 'translateY(0)';
            }
        });
    }, observerOptions);
    
    // Observe all cards
    document.querySelectorAll('.card').forEach(card => {
        card.style.opacity = '0';
        card.style.transform = 'translateY(20px)';
        card.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
        observer.observe(card);
    });
}

// Update Date and Time
function updateDateTime() {
    const now = new Date();
    const timelineItems = document.querySelectorAll('.timeline-item');
    
    // Update relative times for deadlines
    timelineItems.forEach(item => {
        const dateElement = item.querySelector('.timeline-date');
        if (dateElement) {
            const day = parseInt(dateElement.querySelector('.day').textContent);
            const month = dateElement.querySelector('.month').textContent;
            
            // Calculate days difference (simplified)
            const targetDate = new Date(now.getFullYear(), getMonthNumber(month) - 1, day);
            const diffTime = targetDate - now;
            const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
            
            const badge = item.querySelector('.status-badge');
            if (badge) {
                if (diffDays === 1) {
                    badge.textContent = 'Tomorrow';
                    badge.classList.add('warning');
                } else if (diffDays === 0) {
                    badge.textContent = 'Today';
                    badge.classList.add('warning');
                } else if (diffDays > 0) {
                    badge.textContent = `In ${diffDays} days`;
                    badge.classList.remove('warning');
                }
            }
        }
    });
}

// Helper function to convert month name to number
function getMonthNumber(monthName) {
    const months = {
        'Jan': 1, 'Feb': 2, 'Mar': 3, 'Apr': 4, 'May': 5, 'Jun': 6,
        'Jul': 7, 'Aug': 8, 'Sep': 9, 'Oct': 10, 'Nov': 11, 'Dec': 12
    };
    return months[monthName] || 1;
}

// Modal Functions
function showModal(title, content, actionCallback) {
    const modal = document.getElementById('modal');
    const modalTitle = document.getElementById('modalTitle');
    const modalBody = document.getElementById('modalBody');
    const modalAction = document.getElementById('modalAction');
    
    modalTitle.textContent = title;
    modalBody.innerHTML = content;
    
    modal.classList.add('show');
    
    // Set up action button
    modalAction.onclick = function() {
        if (actionCallback) {
            actionCallback();
        }
        closeModal();
    };
}

function closeModal() {
    const modal = document.getElementById('modal');
    modal.classList.remove('show');
}

// Toast Notification
function showToast(message, duration = 3000) {
    const toast = document.getElementById('toast');
    const toastMessage = document.getElementById('toastMessage');
    
    toastMessage.textContent = message;
    toast.classList.add('show');
    
    setTimeout(() => {
        toast.classList.remove('show');
    }, duration);
}

// Quick Action Functions
function accessLesson() {
    const content = `
        <div class="lesson-selector">
            <h3>Select a Course</h3>
            <div style="display: flex; flex-direction: column; gap: 12px; margin-top: 16px;">
                <button class="course-select-btn" onclick="openCourse('math')" style="padding: 12px; background: #F9FAFC; border: 1px solid #E5E7EB; border-radius: 8px; text-align: left; cursor: pointer;">
                    <div style="display: flex; align-items: center; gap: 12px;">
                        <span style="font-size: 20px;">📐</span>
                        <div>
                            <div style="font-weight: 600;">Advanced Mathematics</div>
                            <div style="font-size: 12px; color: #6B6B6B;">Continue Lesson 25: Calculus</div>
                        </div>
                    </div>
                </button>
                <button class="course-select-btn" onclick="openCourse('physics')" style="padding: 12px; background: #F9FAFC; border: 1px solid #E5E7EB; border-radius: 8px; text-align: left; cursor: pointer;">
                    <div style="display: flex; align-items: center; gap: 12px;">
                        <span style="font-size: 20px;">🔬</span>
                        <div>
                            <div style="font-weight: 600;">Physics Lab</div>
                            <div style="font-size: 12px; color: #6B6B6B;">Continue Lesson 19: Thermodynamics</div>
                        </div>
                    </div>
                </button>
                <button class="course-select-btn" onclick="openCourse('literature')" style="padding: 12px; background: #F9FAFC; border: 1px solid #E5E7EB; border-radius: 8px; text-align: left; cursor: pointer;">
                    <div style="display: flex; align-items: center; gap: 12px;">
                        <span style="font-size: 20px;">📚</span>
                        <div>
                            <div style="font-weight: 600;">English Literature</div>
                            <div style="font-size: 12px; color: #6B6B6B;">Continue Lesson 28: Shakespeare</div>
                        </div>
                    </div>
                </button>
                <button class="course-select-btn" onclick="openCourse('cs')" style="padding: 12px; background: #F9FAFC; border: 1px solid #E5E7EB; border-radius: 8px; text-align: left; cursor: pointer;">
                    <div style="display: flex; align-items: center; gap: 12px;">
                        <span style="font-size: 20px;">💻</span>
                        <div>
                            <div style="font-weight: 600;">Computer Science</div>
                            <div style="font-size: 12px; color: #6B6B6B;">Continue Lesson 18: Algorithms</div>
                        </div>
                    </div>
                </button>
            </div>
        </div>
    `;
    
    showModal('Access Lessons', content, null);
}

function openCourse(course) {
    closeModal();
    showToast(`Opening ${course === 'math' ? 'Advanced Mathematics' : course === 'physics' ? 'Physics Lab' : course === 'literature' ? 'English Literature' : 'Computer Science'} lesson...`);
}

function submitWork() {
    const content = `
        <div class="submit-form">
            <h3>Submit Assignment</h3>
            <form style="margin-top: 16px;">
                <div style="margin-bottom: 16px;">
                    <label style="display: block; margin-bottom: 8px; font-size: 14px; font-weight: 500;">Select Assignment</label>
                    <select style="width: 100%; padding: 10px; border: 1px solid #E5E7EB; border-radius: 8px; font-size: 14px;">
                        <option>Calculus Problem Set #8</option>
                        <option>Lab Report: Thermodynamics</option>
                        <option>Essay: Shakespeare Analysis</option>
                    </select>
                </div>
                <div style="margin-bottom: 16px;">
                    <label style="display: block; margin-bottom: 8px; font-size: 14px; font-weight: 500;">Upload File</label>
                    <input type="file" style="width: 100%; padding: 10px; border: 1px solid #E5E7EB; border-radius: 8px; font-size: 14px;">
                </div>
                <div style="margin-bottom: 16px;">
                    <label style="display: block; margin-bottom: 8px; font-size: 14px; font-weight: 500;">Additional Notes (Optional)</label>
                    <textarea style="width: 100%; padding: 10px; border: 1px solid #E5E7EB; border-radius: 8px; font-size: 14px; min-height: 80px; resize: vertical;" placeholder="Add any notes or comments..."></textarea>
                </div>
            </form>
        </div>
    `;
    
    showModal('Submit Work', content, function() {
        showToast('Assignment submitted successfully!');
    });
}

function submitAssignment(assignmentId) {
    const assignmentNames = {
        'calc8': 'Calculus Problem Set #8',
        'lab1': 'Lab Report: Thermodynamics',
        'essay1': 'Essay: Shakespeare Analysis'
    };
    
    const content = `
        <div class="submit-form">
            <h3>Submit: ${assignmentNames[assignmentId]}</h3>
            <form style="margin-top: 16px;">
                <div style="margin-bottom: 16px;">
                    <label style="display: block; margin-bottom: 8px; font-size: 14px; font-weight: 500;">Upload Your Work</label>
                    <input type="file" style="width: 100%; padding: 10px; border: 1px solid #E5E7EB; border-radius: 8px; font-size: 14px;">
                </div>
                <div style="margin-bottom: 16px;">
                    <label style="display: block; margin-bottom: 8px; font-size: 14px; font-weight: 500;">Comments (Optional)</label>
                    <textarea style="width: 100%; padding: 10px; border: 1px solid #E5E7EB; border-radius: 8px; font-size: 14px; min-height: 60px; resize: vertical;" placeholder="Add any comments..."></textarea>
                </div>
                <div style="padding: 12px; background: #F9FAFC; border-radius: 8px; font-size: 12px; color: #6B6B6B;">
                    <strong>Note:</strong> Make sure your file is in PDF or DOCX format and doesn't exceed 10MB.
                </div>
            </form>
        </div>
    `;
    
    showModal('Submit Assignment', content, function() {
        showToast(`${assignmentNames[assignmentId]} submitted successfully!`);
        
        // Update UI to show submitted status
        const assignmentItems = document.querySelectorAll('.assignment-item');
        assignmentItems.forEach(item => {
            const title = item.querySelector('.assignment-title');
            if (title && title.textContent === assignmentNames[assignmentId]) {
                const submitBtn = item.querySelector('.btn-primary');
                if (submitBtn) {
                    submitBtn.textContent = 'Submitted';
                    submitBtn.disabled = true;
                    submitBtn.style.background = '#4CAF50';
                    submitBtn.style.cursor = 'default';
                }
            }
        });
    });
}

function viewGrades() {
    const content = `
        <div class="grades-view">
            <h3>Your Grades</h3>
            <div style="margin-top: 16px;">
                <div style="padding: 16px; background: #F9FAFC; border-radius: 8px; margin-bottom: 12px;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                        <span style="font-weight: 600;">Advanced Mathematics</span>
                        <span style="font-size: 20px; font-weight: 700; color: #3A7BFF;">A</span>
                    </div>
                    <div style="font-size: 12px; color: #6B6B6B;">
                        <div style="display: flex; justify-content: space-between; margin-bottom: 4px;">
                            <span>Quizzes Average:</span>
                            <span>92%</span>
                        </div>
                        <div style="display: flex; justify-content: space-between; margin-bottom: 4px;">
                            <span>Assignments:</span>
                            <span>88%</span>
                        </div>
                        <div style="display: flex; justify-content: space-between;">
                            <span>Midterm Exam:</span>
                            <span>95%</span>
                        </div>
                    </div>
                </div>
                <div style="padding: 16px; background: #F9FAFC; border-radius: 8px; margin-bottom: 12px;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                        <span style="font-weight: 600;">Physics Lab</span>
                        <span style="font-size: 20px; font-weight: 700; color: #65D6A6;">B+</span>
                    </div>
                    <div style="font-size: 12px; color: #6B6B6B;">
                        <div style="display: flex; justify-content: space-between; margin-bottom: 4px;">
                            <span>Lab Reports:</span>
                            <span>85%</span>
                        </div>
                        <div style="display: flex; justify-content: space-between; margin-bottom: 4px;">
                            <span>Practicals:</span>
                            <span>82%</span>
                        </div>
                        <div style="display: flex; justify-content: space-between;">
                            <span>Theory Test:</span>
                            <span>88%</span>
                        </div>
                    </div>
                </div>
                <div style="padding: 16px; background: #F9FAFC; border-radius: 8px; margin-bottom: 12px;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                        <span style="font-weight: 600;">English Literature</span>
                        <span style="font-size: 20px; font-weight: 700; color: #3A7BFF;">A+</span>
                    </div>
                    <div style="font-size: 12px; color: #6B6B6B;">
                        <div style="display: flex; justify-content: space-between; margin-bottom: 4px;">
                            <span>Essays:</span>
                            <span>96%</span>
                        </div>
                        <div style="display: flex; justify-content: space-between; margin-bottom: 4px;">
                            <span>Discussions:</span>
                            <span>94%</span>
                        </div>
                        <div style="display: flex; justify-content: space-between;">
                            <span>Reading Tests:</span>
                            <span>98%</span>
                        </div>
                    </div>
                </div>
                <div style="padding: 16px; background: #F9FAFC; border-radius: 8px;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                        <span style="font-weight: 600;">Computer Science</span>
                        <span style="font-size: 20px; font-weight: 700; color: #FFBE3C;">B</span>
                    </div>
                    <div style="font-size: 12px; color: #6B6B6B;">
                        <div style="display: flex; justify-content: space-between; margin-bottom: 4px;">
                            <span>Programming:</span>
                            <span>78%</span>
                        </div>
                        <div style="display: flex; justify-content: space-between; margin-bottom: 4px;">
                            <span>Projects:</span>
                            <span>82%</span>
                        </div>
                        <div style="display: flex; justify-content: space-between;">
                            <span>Theory:</span>
                            <span>80%</span>
                        </div>
                    </div>
                </div>
            </div>
            <div style="margin-top: 16px; padding: 12px; background: #E8F5E9; border-radius: 8px; text-align: center;">
                <div style="font-size: 12px; color: #6B6B6B; margin-bottom: 4px;">Current GPA</div>
                <div style="font-size: 24px; font-weight: 700; color: #4CAF50;">4.2</div>
            </div>
        </div>
    `;
    
    showModal('Your Grades', content, null);
}

function startQuiz() {
    const content = `
        <div class="quiz-starter">
            <h3>Available Quizzes</h3>
            <div style="margin-top: 16px;">
                <div style="padding: 12px; background: #F9FAFC; border: 1px solid #E5E7EB; border-radius: 8px; margin-bottom: 12px; cursor: pointer;" onclick="beginQuiz('math')">
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <div>
                            <div style="font-weight: 600;">Math Quiz #4: Derivatives</div>
                            <div style="font-size: 12px; color: #6B6B6B;">10 questions • 30 minutes</div>
                        </div>
                        <span style="padding: 4px 8px; background: #FFF3E0; color: #FF9800; border-radius: 4px; font-size: 11px;">New</span>
                    </div>
                </div>
                <div style="padding: 12px; background: #F9FAFC; border: 1px solid #E5E7EB; border-radius: 8px; margin-bottom: 12px; cursor: pointer;" onclick="beginQuiz('physics')">
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <div>
                            <div style="font-weight: 600;">Physics Practice Test</div>
                            <div style="font-size: 12px; color: #6B6B6B;">15 questions • 45 minutes</div>
                        </div>
                        <span style="padding: 4px 8px; background: #E3F2FD; color: #2196F3; border-radius: 4px; font-size: 11px;">Practice</span>
                    </div>
                </div>
                <div style="padding: 12px; background: #F9FAFC; border: 1px solid #E5E7EB; border-radius: 8px; cursor: pointer;" onclick="beginQuiz('review')">
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <div>
                            <div style="font-weight: 600;">Weekly Review Quiz</div>
                            <div style="font-size: 12px; color: #6B6B6B;">20 questions • Mixed subjects</div>
                        </div>
                        <span style="padding: 4px 8px; background: #F3E5F5; color: #9C27B0; border-radius: 4px; font-size: 11px;">Review</span>
                    </div>
                </div>
            </div>
            <div style="margin-top: 16px; padding: 12px; background: #FFF9C4; border-radius: 8px; font-size: 12px;">
                <strong>Tip:</strong> Make sure you have a stable internet connection before starting a quiz.
            </div>
        </div>
    `;
    
    showModal('Start Quiz', content, null);
}

function beginQuiz(type) {
    closeModal();
    showToast(`Starting ${type === 'math' ? 'Math Quiz' : type === 'physics' ? 'Physics Practice Test' : 'Weekly Review Quiz'}...`);
}

function joinClass() {
    const content = `
        <div class="join-class">
            <h3>Join Virtual Class</h3>
            <div style="margin-top: 16px;">
                <div style="padding: 16px; background: #E8F5E9; border: 1px solid #4CAF50; border-radius: 8px; margin-bottom: 12px;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                        <div>
                            <div style="font-weight: 600; color: #2E7D32;">Live Now: Advanced Mathematics</div>
                            <div style="font-size: 12px; color: #558B2F;">Prof. Johnson • Started 10 mins ago</div>
                        </div>
                        <div style="width: 8px; height: 8px; background: #4CAF50; border-radius: 50%; animation: pulse 2s infinite;"></div>
                    </div>
                    <button onclick="joinLiveClass('math')" style="width: 100%; padding: 8px; background: #4CAF50; color: white; border: none; border-radius: 8px; font-weight: 500; cursor: pointer;">Join Now</button>
                </div>
                <div style="padding: 16px; background: #F9FAFC; border: 1px solid #E5E7EB; border-radius: 8px; margin-bottom: 12px;">
                    <div style="font-weight: 600;">Physics Lab Discussion</div>
                    <div style="font-size: 12px; color: #6B6B6B; margin-bottom: 8px;">Tomorrow at 2:00 PM</div>
                    <button onclick="setReminder('physics')" style="width: 100%; padding: 8px; background: white; color: #3A7BFF; border: 1px solid #3A7BFF; border-radius: 8px; font-weight: 500; cursor: pointer;">Set Reminder</button>
                </div>
                <div style="padding: 16px; background: #F9FAFC; border: 1px solid #E5E7EB; border-radius: 8px;">
                    <div style="font-weight: 600;">Literature Study Group</div>
                    <div style="font-size: 12px; color: #6B6B6B; margin-bottom: 8px;">Friday at 4:00 PM</div>
                    <button onclick="setReminder('literature')" style="width: 100%; padding: 8px; background: white; color: #3A7BFF; border: 1px solid #3A7BFF; border-radius: 8px; font-weight: 500; cursor: pointer;">Set Reminder</button>
                </div>
            </div>
        </div>
        <style>
            @keyframes pulse {
                0% { opacity: 1; }
                50% { opacity: 0.5; }
                100% { opacity: 1; }
            }
        </style>
    `;
    
    showModal('Join Virtual Class', content, null);
}

function joinLiveClass(className) {
    closeModal();
    showToast(`Joining live ${className === 'math' ? 'Mathematics' : className} class...`);
}

function setReminder(className) {
    showToast(`Reminder set for ${className === 'physics' ? 'Physics Lab Discussion' : 'Literature Study Group'}`);
}

function downloadMaterials() {
    const content = `
        <div class="download-materials">
            <h3>Course Materials</h3>
            <div style="margin-top: 16px;">
                <div style="margin-bottom: 16px;">
                    <h4 style="font-size: 14px; margin-bottom: 8px; color: #6B6B6B;">Recent Uploads</h4>
                    <div style="display: flex; flex-direction: column; gap: 8px;">
                        <div style="padding: 12px; background: #F9FAFC; border-radius: 8px; display: flex; justify-content: space-between; align-items: center;">
                            <div style="display: flex; align-items: center; gap: 12px;">
                                <span style="font-size: 20px;">📄</span>
                                <div>
                                    <div style="font-size: 14px; font-weight: 500;">Calculus Chapter 8 Notes</div>
                                    <div style="font-size: 11px; color: #6B6B6B;">PDF • 2.4 MB</div>
                                </div>
                            </div>
                            <button onclick="downloadFile('calc-notes')" style="padding: 6px 12px; background: #3A7BFF; color: white; border: none; border-radius: 6px; font-size: 12px; cursor: pointer;">Download</button>
                        </div>
                        <div style="padding: 12px; background: #F9FAFC; border-radius: 8px; display: flex; justify-content: space-between; align-items: center;">
                            <div style="display: flex; align-items: center; gap: 12px;">
                                <span style="font-size: 20px;">📊</span>
                                <div>
                                    <div style="font-size: 14px; font-weight: 500;">Physics Lab Data Sheet</div>
                                    <div style="font-size: 11px; color: #6B6B6B;">Excel • 856 KB</div>
                                </div>
                            </div>
                            <button onclick="downloadFile('physics-data')" style="padding: 6px 12px; background: #3A7BFF; color: white; border: none; border-radius: 6px; font-size: 12px; cursor: pointer;">Download</button>
                        </div>
                        <div style="padding: 12px; background: #F9FAFC; border-radius: 8px; display: flex; justify-content: space-between; align-items: center;">
                            <div style="display: flex; align-items: center; gap: 12px;">
                                <span style="font-size: 20px;">📚</span>
                                <div>
                                    <div style="font-size: 14px; font-weight: 500;">Shakespeare Study Guide</div>
                                    <div style="font-size: 11px; color: #6B6B6B;">PDF • 1.8 MB</div>
                                </div>
                            </div>
                            <button onclick="downloadFile('shakespeare-guide')" style="padding: 6px 12px; background: #3A7BFF; color: white; border: none; border-radius: 6px; font-size: 12px; cursor: pointer;">Download</button>
                        </div>
                    </div>
                </div>
                <div>
                    <h4 style="font-size: 14px; margin-bottom: 8px; color: #6B6B6B;">Resource Library</h4>
                    <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 8px;">
                        <button onclick="openLibrary('textbooks')" style="padding: 12px; background: white; border: 1px solid #E5E7EB; border-radius: 8px; cursor: pointer;">
                            <span style="font-size: 20px; display: block; margin-bottom: 4px;">📖</span>
                            <span style="font-size: 12px;">Textbooks</span>
                        </button>
                        <button onclick="openLibrary('videos')" style="padding: 12px; background: white; border: 1px solid #E5E7EB; border-radius: 8px; cursor: pointer;">
                            <span style="font-size: 20px; display: block; margin-bottom: 4px;">🎥</span>
                            <span style="font-size: 12px;">Video Lectures</span>
                        </button>
                        <button onclick="openLibrary('exercises')" style="padding: 12px; background: white; border: 1px solid #E5E7EB; border-radius: 8px; cursor: pointer;">
                            <span style="font-size: 20px; display: block; margin-bottom: 4px;">✏️</span>
                            <span style="font-size: 12px;">Exercises</span>
                        </button>
                        <button onclick="openLibrary('solutions')" style="padding: 12px; background: white; border: 1px solid #E5E7EB; border-radius: 8px; cursor: pointer;">
                            <span style="font-size: 20px; display: block; margin-bottom: 4px;">💡</span>
                            <span style="font-size: 12px;">Solutions</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    `;
    
    showModal('Download Materials', content, null);
}

function downloadFile(fileName) {
    showToast(`Downloading ${fileName.replace('-', ' ')}...`);
    
    // Simulate download progress
    setTimeout(() => {
        showToast(`Download complete: ${fileName.replace('-', ' ')}`);
    }, 2000);
}

function openLibrary(type) {
    closeModal();
    showToast(`Opening ${type} library...`);
}

// Close modal when clicking outside
window.onclick = function(event) {
    const modal = document.getElementById('modal');
    if (event.target === modal) {
        closeModal();
    }
}

// Responsive Chart Resizing
window.addEventListener('resize', function() {
    const canvas = document.getElementById('performanceChart');
    if (canvas) {
        const ctx = canvas.getContext('2d');
        drawChart(ctx, currentChartView);
    }
});

// Add keyboard shortcuts
document.addEventListener('keydown', function(e) {
    // ESC to close modal
    if (e.key === 'Escape') {
        closeModal();
    }
    
    // Ctrl/Cmd + K to quick search (future feature)
    if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
        e.preventDefault();
        showToast('Quick search coming soon!');
    }
});

// Initialize sample notifications
setTimeout(() => {
    showToast('Welcome back! You have 3 new assignments.');
}, 1000);

// Simulate real-time updates
setInterval(() => {
    // Update study time
    const statValues = document.querySelectorAll('.stat-value');
    if (statValues.length > 1) {
        const currentHours = parseFloat(statValues[0].textContent);
        statValues[0].textContent = (currentHours + 0.1).toFixed(1);
    }
}, 60000); // Update every minute

// Add smooth scrolling for internal navigation
document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function (e) {
        e.preventDefault();
        const target = document.querySelector(this.getAttribute('href'));
        if (target) {
            target.scrollIntoView({
                behavior: 'smooth',
                block: 'start'
            });
        }
    });
});