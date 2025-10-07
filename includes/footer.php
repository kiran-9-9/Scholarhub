    <script src="js/main.js"></script>
    <?php if (isset($additionalScripts)): ?>
    <?php echo $additionalScripts; ?>
    <?php endif; ?>
    </div><!-- Close container -->
    <footer class="footer" style="margin-top: 3rem; padding: 2rem 0; background: white; border-top: 1px solid #eee;">
        <div class="container">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <div>
                    <h3 style="color: var(--primary-color); margin-bottom: 1rem;">ScholarHub</h3>
                    <p style="color: var(--secondary-color);">Empowering education through scholarships</p>
                </div>
                <div style="text-align: right;">
                    <p style="color: var(--secondary-color);">&copy; <?php echo date('Y'); ?> ScholarHub. All rights reserved.</p>
                </div>
            </div>
        </div>
    </footer>
</body>
</html> 