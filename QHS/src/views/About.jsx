import React from 'react';
import {
  Container,
  Box,
  Typography,
  Card,
  CardContent,
  Grid,
  useTheme,
  useMediaQuery,
  Stack,
  Paper,
  Divider,
} from '@mui/material';
import {
  School as SchoolIcon,
  Groups as GroupsIcon,
  EmojiEvents as TargetIcon,
  Handshake as HandshakeIcon,
} from '@mui/icons-material';

export default function About() {
  const theme = useTheme();
  const isMobile = useMediaQuery(theme.breakpoints.down('sm'));

  const missions = [
    {
      icon: SchoolIcon,
      title: 'Education',
      description: 'Empowering students with access to quality laboratory equipment and resources for hands-on learning.',
    },
    {
      icon: HandshakeIcon,
      title: 'Collaboration',
      description: 'Fostering cooperation between departments and institutions to share resources efficiently.',
    },
    {
      icon: TargetIcon,
      title: 'Excellence',
      description: 'Maintaining high standards in equipment management and laboratory support services.',
    },
    {
      icon: GroupsIcon,
      title: 'Community',
      description: 'Building a supportive community of researchers, students, and faculty members.',
    },
  ];

  return (
    <Container maxWidth="lg" sx={{ py: { xs: 3, md: 6 } }}>
      {/* Hero Section */}
      <Box
        sx={{
          textAlign: 'center',
          mb: { xs: 4, md: 8 },
          py: { xs: 3, md: 5 },
        }}
      >
        <Typography
          variant="h3"
          component="h1"
          sx={{
            fontWeight: 'bold',
            color: theme.palette.primary.main,
            mb: 2,
          }}
        >
          About Us
        </Typography>
        <Typography
          variant="h6"
          color="textSecondary"
          sx={{
            maxWidth: '600px',
            mx: 'auto',
            mb: 3,
          }}
        >
          We are dedicated to providing comprehensive laboratory equipment management
          and support services to educational institutions and research organizations.
        </Typography>
      </Box>

      {/* Mission & Vision Section */}
      <Box sx={{ mb: { xs: 4, md: 8 } }}>
        <Grid container spacing={3}>
          <Grid item xs={12} md={6}>
            <Paper elevation={2} sx={{ p: 3, height: '100%' }}>
              <Typography variant="h5" gutterBottom sx={{ fontWeight: 'bold', mb: 2 }}>
                Our Mission
              </Typography>
              <Typography variant="body1" color="textSecondary" paragraph>
                To streamline and optimize laboratory equipment management by providing
                an intuitive platform that facilitates easy borrowing, tracking, and
                maintenance of laboratory resources.
              </Typography>
              <Typography variant="body1" color="textSecondary">
                We aim to reduce waste, improve accessibility, and ensure that educational
                institutions can maximize the value of their equipment investments.
              </Typography>
            </Paper>
          </Grid>

          <Grid item xs={12} md={6}>
            <Paper elevation={2} sx={{ p: 3, height: '100%' }}>
              <Typography variant="h5" gutterBottom sx={{ fontWeight: 'bold', mb: 2 }}>
                Our Vision
              </Typography>
              <Typography variant="body1" color="textSecondary" paragraph>
                To become the leading laboratory management solution that empowers
                institutions to make data-driven decisions about their equipment resources.
              </Typography>
              <Typography variant="body1" color="textSecondary">
                We envision a future where laboratory equipment is efficiently utilized,
                properly maintained, and accessible to all authorized users.
              </Typography>
            </Paper>
          </Grid>
        </Grid>
      </Box>

      {/* Core Values Section */}
      <Divider sx={{ my: { xs: 3, md: 5 } }} />

      <Box sx={{ mb: { xs: 4, md: 8 } }}>
        <Typography
          variant="h4"
          component="h2"
          sx={{
            fontWeight: 'bold',
            textAlign: 'center',
            mb: { xs: 3, md: 4 },
            color: theme.palette.primary.main,
          }}
        >
          Our Core Values
        </Typography>

        <Grid container spacing={3}>
          {missions.map((mission, index) => {
            const IconComponent = mission.icon;
            return (
              <Grid item xs={12} sm={6} md={3} key={index}>
                <Card
                  sx={{
                    height: '100%',
                    display: 'flex',
                    flexDirection: 'column',
                    alignItems: 'center',
                    textAlign: 'center',
                    p: 2,
                    transition: 'transform 0.3s ease, box-shadow 0.3s ease',
                    '&:hover': {
                      transform: 'translateY(-8px)',
                      boxShadow: 4,
                    },
                  }}
                >
                  <Box
                    sx={{
                      mb: 2,
                      p: 2,
                      borderRadius: '50%',
                      backgroundColor: theme.palette.primary.light,
                      color: theme.palette.primary.main,
                    }}
                  >
                    <IconComponent sx={{ fontSize: 40 }} />
                  </Box>
                  <Typography
                    variant="h6"
                    gutterBottom
                    sx={{ fontWeight: 'bold' }}
                  >
                    {mission.title}
                  </Typography>
                  <Typography variant="body2" color="textSecondary">
                    {mission.description}
                  </Typography>
                </Card>
              </Grid>
            );
          })}
        </Grid>
      </Box>

      {/* Features Section */}
      <Divider sx={{ my: { xs: 3, md: 5 } }} />

      <Box sx={{ mb: { xs: 4, md: 8 } }}>
        <Typography
          variant="h4"
          component="h2"
          sx={{
            fontWeight: 'bold',
            textAlign: 'center',
            mb: { xs: 3, md: 4 },
            color: theme.palette.primary.main,
          }}
        >
          What We Offer
        </Typography>

        <Grid container spacing={2}>
          <Grid item xs={12} sm={6} md={4}>
            <Paper sx={{ p: 2 }}>
              <Typography variant="h6" gutterBottom sx={{ fontWeight: 'bold' }}>
                Equipment Management
              </Typography>
              <Typography variant="body2" color="textSecondary">
                Comprehensive tracking and management of laboratory equipment across departments.
              </Typography>
            </Paper>
          </Grid>

          <Grid item xs={12} sm={6} md={4}>
            <Paper sx={{ p: 2 }}>
              <Typography variant="h6" gutterBottom sx={{ fontWeight: 'bold' }}>
                Easy Borrowing System
              </Typography>
              <Typography variant="body2" color="textSecondary">
                Simple and intuitive interface for requesting and borrowing equipment.
              </Typography>
            </Paper>
          </Grid>

          <Grid item xs={12} sm={6} md={4}>
            <Paper sx={{ p: 2 }}>
              <Typography variant="h6" gutterBottom sx={{ fontWeight: 'bold' }}>
                Real-time Tracking
              </Typography>
              <Typography variant="body2" color="textSecondary">
                Monitor equipment status and borrowing history in real-time.
              </Typography>
            </Paper>
          </Grid>

          <Grid item xs={12} sm={6} md={4}>
            <Paper sx={{ p: 2 }}>
              <Typography variant="h6" gutterBottom sx={{ fontWeight: 'bold' }}>
                Maintenance Support
              </Typography>
              <Typography variant="body2" color="textSecondary">
                Track maintenance schedules and equipment health status.
              </Typography>
            </Paper>
          </Grid>

          <Grid item xs={12} sm={6} md={4}>
            <Paper sx={{ p: 2 }}>
              <Typography variant="h6" gutterBottom sx={{ fontWeight: 'bold' }}>
                Analytics & Reports
              </Typography>
              <Typography variant="body2" color="textSecondary">
                Generate detailed reports on equipment utilization and trends.
              </Typography>
            </Paper>
          </Grid>

          <Grid item xs={12} sm={6} md={4}>
            <Paper sx={{ p: 2 }}>
              <Typography variant="h6" gutterBottom sx={{ fontWeight: 'bold' }}>
                User Support
              </Typography>
              <Typography variant="body2" color="textSecondary">
                Dedicated support for all users and administrators.
              </Typography>
            </Paper>
          </Grid>
        </Grid>
      </Box>

      {/* Contact Section */}
      <Divider sx={{ my: { xs: 3, md: 5 } }} />

      <Box sx={{ textAlign: 'center', py: 4 }}>
        <Typography variant="h5" gutterBottom sx={{ fontWeight: 'bold' }}>
          Have Questions?
        </Typography>
        <Typography variant="body1" color="textSecondary" paragraph>
          Contact our support team for more information about our services.
        </Typography>
        <Stack direction="row" spacing={2} justifyContent="center" sx={{ mt: 3 }}>
          <Typography variant="body2">
            <strong>Email:</strong> support@qhs.edu
          </Typography>
          <Typography variant="body2">
            <strong>Phone:</strong> (555) 123-4567
          </Typography>
        </Stack>
      </Box>
    </Container>
  );
}
