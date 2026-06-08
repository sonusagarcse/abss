<?php
// Include the database config and header if needed
require_once 'config/db.php';
require_once 'includes/header.php';
?>

<style>
    .hedp-container {
        font-family: 'Inter', 'Roboto', sans-serif;
        background-color: #f8f9fa;
        padding: 40px 15px;
    }
    .hedp-wrapper {
        max-width: 1100px;
        margin: 0 auto;
        background: #fff;
        border-radius: 12px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        overflow: hidden;
    }
    .hedp-header {
        text-align: center;
        padding: 40px 20px;
        background: linear-gradient(135deg, #f0f8ff, #e6f2ff);
        position: relative;
    }
    .hedp-org {
        font-size: 1.5rem;
        font-weight: 700;
        color: #1a365d;
        margin-bottom: 10px;
    }
    .hedp-title {
        font-size: 5rem;
        font-weight: 900;
        letter-spacing: 2px;
        margin: 0;
        line-height: 1;
    }
    .hedp-title span.h { color: #1a365d; }
    .hedp-title span.e { color: #e53e3e; }
    .hedp-title span.d { color: #38a169; }
    .hedp-title span.p { color: #dd6b20; }
    
    .hedp-subtitle {
        background-color: #1a365d;
        color: white;
        display: inline-block;
        padding: 8px 25px;
        border-radius: 30px;
        font-size: 1.2rem;
        font-weight: 600;
        margin-top: 15px;
    }
    
    .hedp-banner {
        background: #c53030;
        color: white;
        text-align: center;
        padding: 15px;
        font-size: 2rem;
        font-weight: bold;
    }
    .hedp-banner span {
        color: #fbd38d;
    }
    
    .hedp-target-audience {
        text-align: center;
        font-size: 1.3rem;
        font-weight: 600;
        padding: 15px;
        color: #2d3748;
        background: #edf2f7;
    }

    .hedp-content {
        display: flex;
        flex-wrap: wrap;
        padding: 30px;
        gap: 30px;
    }
    
    .hedp-column {
        flex: 1;
        min-width: 300px;
        background: #fff;
        border: 2px solid #e2e8f0;
        border-radius: 12px;
        overflow: hidden;
    }
    
    .col-header-1 {
        background: #2b6cb0;
        color: white;
        padding: 15px;
        text-align: center;
        font-size: 1.4rem;
        font-weight: bold;
    }
    
    .col-header-2 {
        background: #2f855a;
        color: white;
        padding: 15px;
        text-align: center;
        font-size: 1.4rem;
        font-weight: bold;
    }
    
    .col-sub {
        text-align: center;
        background: #fefcbf;
        color: #975a16;
        padding: 10px;
        font-weight: bold;
        font-size: 1.2rem;
        border-bottom: 2px solid #e2e8f0;
    }
    
    .hedp-list {
        list-style: none;
        padding: 0;
        margin: 0;
    }
    
    .hedp-list li {
        padding: 15px 20px;
        border-bottom: 1px solid #edf2f7;
        display: flex;
        align-items: center;
        font-size: 1.1rem;
        font-weight: 600;
        color: #2d3748;
        transition: background 0.3s;
    }
    .hedp-list li:hover {
        background: #f7fafc;
    }
    .hedp-list li:last-child {
        border-bottom: none;
    }
    
    .hedp-list li i {
        font-size: 1.5rem;
        margin-right: 15px;
        color: #4a5568;
        width: 30px;
        text-align: center;
    }
    
    .hedp-list li span {
        display: block;
        font-size: 0.9rem;
        font-weight: normal;
        color: #718096;
        margin-top: 3px;
    }
    
    .hedp-features {
        display: flex;
        flex-wrap: wrap;
        background: #f7fafc;
        border-top: 2px solid #e2e8f0;
        border-bottom: 2px solid #e2e8f0;
    }
    
    .feature-item {
        flex: 1;
        min-width: 200px;
        padding: 20px;
        text-align: center;
        border-right: 1px solid #e2e8f0;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 15px;
        font-weight: bold;
        color: #4a5568;
    }
    .feature-item:last-child {
        border-right: none;
    }
    .feature-item i {
        font-size: 2.5rem;
        color: #c53030;
    }
    
    .hedp-footer {
        display: flex;
        flex-wrap: wrap;
        background: #fff;
    }
    .contact-info {
        flex: 1;
        min-width: 300px;
        padding: 20px;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 15px;
        font-size: 1.2rem;
        color: #2d3748;
    }
    .contact-info i {
        font-size: 2rem;
        color: #2b6cb0;
    }
    .contact-info strong {
        font-size: 1.8rem;
        display: block;
        color: #1a365d;
    }
    .contact-website {
        border-left: 1px solid #e2e8f0;
    }
    
    .hedp-tagline {
        background: #2b6cb0;
        color: white;
        text-align: center;
        padding: 15px;
        font-size: 1.3rem;
        font-weight: bold;
        width: 100%;
    }
    
    .sun-badge {
        position: absolute;
        top: 20px;
        right: 40px;
        background: #fbd38d;
        color: #c53030;
        width: 120px;
        height: 120px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        text-align: center;
        font-weight: bold;
        font-size: 1.1rem;
        box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        border: 4px dashed #dd6b20;
        transform: rotate(15deg);
    }
    
    @media (max-width: 768px) {
        .sun-badge {
            display: none;
        }
        .hedp-title {
            font-size: 3.5rem;
        }
        .hedp-content {
            padding: 15px;
        }
        .contact-website {
            border-left: none;
            border-top: 1px solid #e2e8f0;
        }
    }
</style>

<!-- Font Awesome for icons -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<div class="hedp-container">
    <div class="hedp-wrapper">
        <div class="hedp-header">
            <div class="hedp-org">लोक कला विकास मंच<br>द्वारा संचालित</div>
            <h1 class="hedp-title">
                <span class="h">H</span><span class="e">E</span><span class="d">D</span><span class="p">P</span>
            </h1>
            <div class="hedp-subtitle">(Holistic Education & Development Program)</div>
            
            <div class="sun-badge">
                गर्मी की<br>छुट्टी में<br>करें<br>निःशुल्क<br>तैयारी
            </div>
        </div>
        
        <div class="hedp-banner">
            गर्मी की छुट्टी में करें <span>निःशुल्क तैयारी</span>
        </div>
        
        <div class="hedp-target-audience">
            बिहार एवं झारखंड के विद्यार्थियों के लिए सुनहरा अवसर
        </div>
        
        <div class="hedp-content">
            <!-- Column 1 -->
            <div class="hedp-column">
                <div class="col-header-1">1. स्कूल छात्र-छात्राओं के लिए</div>
                <div class="col-sub">तैयारी कराएं</div>
                <ul class="hedp-list">
                    <li>
                        <i class="fas fa-book-open" style="color: #2b6cb0;"></i>
                        <div>
                            नवोदय विद्यालय
                            <span>(Jawahar Navodaya Vidyalaya)</span>
                        </div>
                    </li>
                    <li>
                        <i class="fas fa-shield-alt" style="color: #2b6cb0;"></i>
                        <div>
                            सैनिक स्कूल
                            <span>(Sainik School)</span>
                        </div>
                    </li>
                    <li>
                        <i class="fas fa-university" style="color: #2b6cb0;"></i>
                        <div>
                            BHU प्रवेश परीक्षा
                            <span>(Banaras Hindu University)</span>
                        </div>
                    </li>
                    <li>
                        <i class="fas fa-tree" style="color: #38a169;"></i>
                        <div>
                            सिमुलतला आवासीय विद्यालय
                            <span>(Simultala Awasiya Vidyalaya)</span>
                        </div>
                    </li>
                    <li>
                        <i class="fas fa-mountain" style="color: #38a169;"></i>
                        <div>
                            नेतरहाट आवासीय विद्यालय
                            <span>(Netarhat Awasiya Vidyalaya)</span>
                        </div>
                    </li>
                </ul>
            </div>
            
            <!-- Column 2 -->
            <div class="hedp-column">
                <div class="col-header-2">2. कॉलेज छात्र-छात्राओं के लिए</div>
                <div class="col-sub">निःशुल्क कोर्स करें</div>
                <ul class="hedp-list">
                    <li>
                        <i class="fas fa-desktop" style="color: #4a5568;"></i>
                        <div>
                            डाटा एंट्री
                            <span>(Data Entry)</span>
                        </div>
                    </li>
                    <li>
                        <i class="fas fa-calculator" style="color: #4a5568;"></i>
                        <div>
                            एकाउंटिंग
                            <span>(Accounting)</span>
                        </div>
                    </li>
                    <li>
                        <i class="fas fa-solar-panel" style="color: #d69e2e;"></i>
                        <div>
                            सोलर LED टेक्नोलॉजी
                            <span>(Solar LED Technology)</span>
                        </div>
                    </li>
                    <li>
                        <i class="fas fa-code" style="color: #2b6cb0;"></i>
                        <div>
                            वेब डेवलपमेंट
                            <span>(Web Development)</span>
                        </div>
                    </li>
                </ul>
            </div>
        </div>
        
        <!-- Features -->
        <div class="hedp-features">
            <div class="feature-item">
                <i class="fas fa-gift"></i>
                <div>पूरी तरह<br>निःशुल्क</div>
            </div>
            <div class="feature-item">
                <i class="fas fa-chalkboard-teacher"></i>
                <div>विशेषज्ञ प्रशिक्षकों<br>द्वारा प्रशिक्षण</div>
            </div>
            <div class="feature-item">
                <i class="fas fa-book-reader"></i>
                <div>सरल भाषा में<br>बेहतरीन तैयारी</div>
            </div>
            <div class="feature-item">
                <i class="fas fa-bullseye"></i>
                <div>उज्ज्वल भविष्य<br>की ओर कदम</div>
            </div>
        </div>
        
        <!-- Footer Contact Info -->
        <div class="hedp-footer">
            <div class="contact-info">
                <i class="fas fa-phone-alt"></i>
                <div>
                    अधिक जानकारी के लिए संपर्क करें
                    <strong>9523012888</strong>
                </div>
            </div>
            <div class="contact-info contact-website">
                <i class="fas fa-globe"></i>
                <div>
                    वेबसाइट पर जाएं
                    <strong>lkvmbihar.in</strong>
                </div>
            </div>
            <div class="hedp-tagline">
                HEDP - हर विद्यार्थी, सशक्त विद्यार्थी, सफल विद्यार्थी
            </div>
        </div>
    </div>
</div>

<?php
// Include the footer
require_once 'includes/footer.php';
?>
