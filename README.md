# Youth For Survival - Non-Profit Organization Website

A comprehensive website for Youth For Survival, a non-profit organization dedicated to empowering youth through education, support, and community development initiatives.

## 🌟 About the Project

Youth For Survival is a South African-based non-profit organization focused on creating opportunities for young people to thrive and become leaders in their communities. This website serves as the digital platform for the organization to showcase its work, engage with supporters, and facilitate donations.

## 🚀 Features

### Core Pages
- **Home**: Landing page with organization overview and mission statement
- **About Us**: Detailed information about the organization's history and values
- **Causes**: Showcase of current initiatives and programs
- **Gallery**: Image gallery with filtering and lightbox functionality
- **News**: Latest updates and announcements
- **Contact**: Contact form and organization information
- **Donate**: Secure donation processing with multiple payment options
- **User Support**: Comprehensive help center and FAQ system

### Technical Features
- **Responsive Design**: Mobile-first approach works on all devices
- **Modern UI/UX**: Clean, accessible interface with smooth animations
- **Database Integration**: MySQL backend for dynamic content
- **Payment Processing**: Integration with Capitec PayMe and other payment methods
- **Image Management**: Dynamic gallery with categorization
- **User Authentication**: Registration and login system
- **Contact Forms**: Functional contact and support forms

## 🛠️ Technology Stack

### Frontend
- HTML5, CSS3, JavaScript (ES6+)
- Responsive Grid and Flexbox layouts
- Font Awesome icons
- Lightbox2 for image viewing

### Backend
- PHP 7.4+
- MySQL Database
- Prepared statements for security

### External Services
- Capitec PayMe payment processing
- Email functionality for notifications

## 📦 Installation

### Prerequisites
- Web server (Apache/Nginx)
- PHP 7.4 or higher
- MySQL 5.7 or higher
- Modern web browser

### For Administrators
1. **Content Management**: Update gallery images and news posts directly through the database
2. **Donation Tracking**: Monitor donations through the database
3. **User Management**: Manage registered users (if authentication is implemented)

### For Visitors
1. **Browse Content**: Explore the organization's work through various sections
2. **Make Donations**: Use the secure donation system to support the cause
3. **Contact Organization**: Reach out through the contact form
4. **View Gallery**: Browse images of events and activities

## 🔧 Customization

### Styling
- Primary colors: Yellow (#e6e600), Red (#e63946), Black (#000)
- Update colors in CSS variables for quick theming
- Modify typography and spacing in the main CSS files

### Content
- Update organization information in respective PHP files
- Add new gallery categories by updating the database
- Modify donation options in the donate.php file

### Functionality
- Extend payment options by integrating additional payment gateways
- Add blog functionality for news section
- Implement user roles for content management

## 🐛 Troubleshooting

### Common Issues

1. **Database Connection Error**
   - Verify database credentials
   - Check if MySQL server is running
   - Ensure database and tables exist

2. **Images Not Loading**
   - Check file paths in image references
   - Verify image file permissions
   - Ensure images are uploaded to correct directories

3. **Forms Not Submitting**
   - Check PHP error logs for form processing issues
   - Verify email configuration for contact forms
   - Ensure required fields are properly validated

4. **Payment Issues**
   - Verify payment gateway credentials
   - Check SSL certificate for secure transactions
   - Test with different payment methods

### Debug Mode
Enable debug mode by adding to PHP files:
```php
error_reporting(E_ALL);
ini_set('display_errors', 1);
```

## 🤝 Contributing

We welcome contributions to improve the Youth For Survival website!

### How to Contribute
1. Fork the repository
2. Create a feature branch (`git checkout -b feature/AmazingFeature`)
3. Commit your changes (`git commit -m 'Add some AmazingFeature'`)
4. Push to the branch (`git push origin feature/AmazingFeature`)
5. Open a Pull Request

### Development Guidelines
- Follow existing code style and structure
- Test changes on multiple devices and browsers
- Ensure all forms have proper validation
- Maintain database security with prepared statements

## 📄 License

This project is licensed under the MIT License - see the [LICENSE.md](LICENSE.md) file for details.

## 🏢 Organization Information

**Youth For Survival**
- Location: Pretoria, Gauteng, South Africa
- Email: youthforsurvival2007@gmail.com
- LinkedIn: [Youth for Survival South Africa](https://www.linkedin.com/company/youth-for-survival-south-africa)

## 🙏 Acknowledgments

- Capitec Bank for payment processing integration
- Font Awesome for icons
- Unsplash for stock photography
- All contributors and volunteers

## 📞 Support

For technical support or questions about this website:
- Create an issue in this repository
- Contact the development team
- Email: youthforsurvival2007@gmail.com

---



*Empowering youth for a better tomorrow*

</div>
