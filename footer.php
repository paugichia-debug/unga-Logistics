    <div class="footer">
        <div class="footer-content">
            <div class="footer-section">
                <h4>🏢 Unga Group PLC</h4>
                <p>Since 1908</p>
                <p>Leading logistics and supply chain solutions provider in Kenya.</p>
                <p>📍 Ngano House, Commercial Street, Industrial Area, Nairobi</p>
                <p>📮 P.O. Box 30386, Nairobi, Kenya</p>
            </div>
            <div class="footer-section">
                <h4>📞 Contact Us</h4>
                <p>📧 customercare@unga.com</p>
                <p>📞 0709 772 000</p>
                <p>📞 0707 202020</p>
                <p>📞 020 7603000</p>
            </div>
            <div class="footer-section">
                <h4>📍 Regional Locations</h4>
                <p>📍 Nairobi</p>
                <p>📍 Eldoret</p>
            </div>
            <div class="footer-section">
                <h4>🔗 Quick Links</h4>
                <p><a href="vehicles.php">Vehicle Management</a></p>
                <p><a href="deliveries.php">Delivery Management</a></p>
                <p><a href="drivers.php">Driver Management</a></p>
                <p><a href="reports.php">Reports & Analytics</a></p>
            </div>
        </div>
        <div class="copyright">
            &copy; <?php echo date('Y'); ?> Unga Group PLC. All rights reserved. | Logistics Management System v1.0
        </div>
    </div>

</div>

<script>
    let slideIndex = 0;
    const slides = document.querySelectorAll('.slide');
    const totalSlides = slides.length;
    function changeSlide() {
        slides.forEach(slide => slide.classList.remove('active'));
        slideIndex = (slideIndex + 1) % totalSlides;
        slides[slideIndex].classList.add('active');
    }
    setInterval(changeSlide, 6000);

    function updateClock() {
        const now = new Date();
        const hours = String(now.getHours()).padStart(2, '0');
        const minutes = String(now.getMinutes()).padStart(2, '0');
        const seconds = String(now.getSeconds()).padStart(2, '0');
        document.getElementById('clockDisplay').textContent = hours + ':' + minutes;
        document.getElementById('secondsDisplay').textContent = ':' + seconds;
    }
    setInterval(updateClock, 1000);
</script>
</body>
</html>
