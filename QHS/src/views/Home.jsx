import React, { useEffect, useState } from 'react';
import {
  Container,
  Box,
  Typography,
  Button,
  Grid,
  Card,
  CardContent,
  CardActions,
  useTheme,
  useMediaQuery,
  Paper,
  Chip,
  Avatar,
  Stack,
} from '@mui/material';
import {
  School as SchoolIcon,
  Inventory2 as InventoryIcon,
  SwapHoriz as BorrowIcon,
  History as HistoryIcon,
  Check as CheckIcon,
  HourglassEmpty as PendingIcon,
  Info as InfoIcon,
} from '@mui/icons-material';
import { useNavigate } from 'react-router-dom';
import axiosClient from '../axiosClient';

export default function Home() {
  const theme = useTheme();
  const isMobile = useMediaQuery(theme.breakpoints.down('sm'));
  const navigate = useNavigate();
  const [user, setUser] = useState(null);
  const [borrowStats, setBorrowStats] = useState({
    active: 0,
    pending: 0,
    returned: 0,
  });
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    const fetchData = async () => {
      try {
        // Fetch user data
        const userRes = await axiosClient.get('/user');
        setUser(userRes.data);

        // Fetch user's transactions to get borrow stats
        try {
          const txRes = await axiosClient.get('/transactions?per_page=1000');
          const txs = txRes.data.data || [];

          // Filter by current user if needed
          const stats = {
            active: txs.filter(t => t.status === 'borrowed').length,
            pending: txs.filter(t => t.status === 'pending').length,
            returned: txs.filter(t => t.status === 'returned').length,
          };
          setBorrowStats(stats);
        } catch (e) {
          console.error('Failed to load transaction stats', e);
        }
      } catch (error) {
        console.error('Failed to load home data', error);
      } finally {
        setLoading(false);
      }
    };

    fetchData();
  }, []);

  const quickActions = [
    {
      icon: <InventoryIcon sx={{ fontSize: 40, color: '#800000' }} />,
      title: 'Browse Equipment',
      description: 'Explore available equipment in our laboratories',
      action: 'Browse Now',
      color: '#fff8f0',
      borderColor: '#d4a5a5',
      onClick: () => navigate('/laboratories'),
    },
    {
      icon: <BorrowIcon sx={{ fontSize: 40, color: '#800000' }} />,
      title: 'New Borrow Request',
      description: 'Request to borrow equipment for your project',
      action: 'Request Now',
      color: '#fff8f0',
      borderColor: '#d4a5a5',
      onClick: () => navigate('/laboratories'),
    },
    {
      icon: <HistoryIcon sx={{ fontSize: 40, color: '#800000' }} />,
      title: 'My Transactions',
      description: 'View your borrow history and current loans',
      action: 'View History',
      color: '#fff8f0',
      borderColor: '#d4a5a5',
      onClick: () => navigate('/borrow-history'),
    },
    {
      icon: <CheckIcon sx={{ fontSize: 40, color: '#800000' }} />,
      title: 'Return Equipment',
      description: 'Return borrowed equipment to the lab',
      action: 'Return Item',
      color: '#fff8f0',
      borderColor: '#d4a5a5',
      onClick: () => navigate('/borrow-history'),
    },
  ];

  const steps = [
    {
      step: 1,
      title: 'Browse Equipment',
      description: 'Visit the inventory section to see available equipment across all laboratories.',
    },
    {
      step: 2,
      title: 'Make a Request',
      description: 'Select the equipment you need and submit a borrow request with your purpose.',
    },
    {
      step: 3,
      title: 'Get Approval',
      description: 'Wait for the laboratory staff to review and approve your request.',
    },
    {
      step: 4,
      title: 'Collect & Use',
      description: 'Once approved, collect the equipment from the laboratory.',
    },
    {
      step: 5,
      title: 'Return',
      description: 'Return the equipment on time in good condition.',
    },
  ];

  return (
    <Container maxWidth="lg" sx={{ py: 4 }}>
      {/* Header Section */}
      <Box sx={{ mb: 6 }}>
        <Typography
          variant={isMobile ? 'h4' : 'h2'}
          fontWeight="bold"
          sx={{ color: '#800000', mb: 2 }}
        >
          Welcome back, {user?.name || 'User'}!
        </Typography>
        <Typography variant="body1" color="text.secondary" sx={{ fontSize: '1.1rem' }}>
          Manage your laboratory equipment inventory and transactions efficiently.
        </Typography>
      </Box>

      {/* Quick Actions Section */}
      <Box sx={{ mb: 6 }}>
        <Typography variant="h5" fontWeight="bold" sx={{ mb: 3 }}>
          What would you like to do?
        </Typography>
        <Grid container spacing={3}>
          {quickActions.map((action, index) => (
            <Grid item xs={12} sm={6} md={3} key={index}>
              <Card
                sx={{
                  height: '100%',
                  display: 'flex',
                  flexDirection: 'column',
                  bgcolor: action.color,
                  border: `2px solid ${action.borderColor}`,
                  transition: 'transform 0.3s, box-shadow 0.3s',
                  cursor: 'pointer',
                  '&:hover': {
                    transform: 'translateY(-8px)',
                    boxShadow: 4,
                  },
                }}
                onClick={action.onClick}
              >
                <CardContent sx={{ flexGrow: 1, pb: 1 }}>
                  <Box sx={{ mb: 2 }}>{action.icon}</Box>
                  <Typography variant="h6" fontWeight="bold" gutterBottom>
                    {action.title}
                  </Typography>
                  <Typography variant="body2" color="text.secondary">
                    {action.description}
                  </Typography>
                </CardContent>
                <CardActions sx={{ justifyContent: 'center', pb: 2 }}>
                  <Button
                    size="small"
                    variant="contained"
                    sx={{ bgcolor: '#800000', color: 'white', '&:hover': { bgcolor: '#600000' } }}
                  >
                    {action.action}
                  </Button>
                </CardActions>
              </Card>
            </Grid>
          ))}
        </Grid>
      </Box>

      {/* How It Works Section */}
      <Box sx={{ mb: 6 }}>
        <Typography variant="h5" fontWeight="bold" sx={{ mb: 3, color: '#800000' }}>
          How It Works
        </Typography>
        <Grid container spacing={2}>
          {steps.map((item, index) => (
            <Grid item xs={12} sm={6} md={2.4} key={index}>
              <Card
                sx={{
                  height: '100%',
                  display: 'flex',
                  flexDirection: 'column',
                  textAlign: 'center',
                  borderTop: `4px solid #800000`,
                }}
              >
                <CardContent sx={{ flexGrow: 1 }}>
                  <Avatar
                    sx={{
                      width: 40,
                      height: 40,
                      bgcolor: '#800000',
                      margin: '0 auto 12px',
                      fontSize: '1.2rem',
                      fontWeight: 'bold',
                    }}
                  >
                    {item.step}
                  </Avatar>
                  <Typography variant="subtitle2" fontWeight="bold" gutterBottom>
                    {item.title}
                  </Typography>
                  <Typography variant="caption" color="text.secondary">
                    {item.description}
                  </Typography>
                </CardContent>
              </Card>
            </Grid>
          ))}
        </Grid>
      </Box>

      {/* Tips Section */}
      <Paper
        sx={{
          p: 3,
          bgcolor: theme.palette.mode === 'dark' ? '#1e1e1e' : '#fff8f0',
          borderLeft: `4px solid #800000`,
          mb: 4,
        }}
      >
        <Stack direction="row" spacing={2} sx={{ mb: 2 }}>
          <InfoIcon sx={{ color: '#800000', fontSize: 24 }} />
          <Typography variant="h6" fontWeight="bold" sx={{ color: '#800000' }}>
            Quick Tips
          </Typography>
        </Stack>
        <Grid container spacing={2}>
          <Grid item xs={12} sm={6}>
            <Typography variant="body2" sx={{ display: 'flex', alignItems: 'center', mb: 1 }}>
              <Chip label="✓" size="small" sx={{ mr: 1 }} />
              Always return equipment on time
            </Typography>
            <Typography variant="body2" sx={{ display: 'flex', alignItems: 'center' }}>
              <Chip label="✓" size="small" sx={{ mr: 1 }} />
              Check equipment condition before accepting
            </Typography>
          </Grid>
          <Grid item xs={12} sm={6}>
            <Typography variant="body2" sx={{ display: 'flex', alignItems: 'center', mb: 1 }}>
              <Chip label="✓" size="small" sx={{ mr: 1 }} />
              Report any damage immediately
            </Typography>
            <Typography variant="body2" sx={{ display: 'flex', alignItems: 'center' }}>
              <Chip label="✓" size="small" sx={{ mr: 1 }} />
              Handle equipment with care
            </Typography>
          </Grid>
        </Grid>
      </Paper>

      {/* CTA Section */}
      <Box sx={{ textAlign: 'center' }}>
        <Typography variant="h6" sx={{ mb: 3, color: theme.palette.text.secondary }}>
          Ready to borrow equipment?
        </Typography>
        <Stack direction={{ xs: 'column', sm: 'row' }} spacing={2} justifyContent="center">
          <Button
            variant="contained"
            size="large"
            startIcon={<InventoryIcon />}
            onClick={() => navigate('/laboratories')}
            sx={{ minWidth: 200, bgcolor: '#800000', color: 'white', '&:hover': { bgcolor: '#600000' } }}
          >
            Browse Equipment
          </Button>
          <Button
            variant="outlined"
            size="large"
            startIcon={<BorrowIcon />}
            onClick={() => navigate('/borrow-history')}
            sx={{ minWidth: 200, color: '#800000', borderColor: '#800000', '&:hover': { bgcolor: 'rgba(128, 0, 0, 0.04)' } }}
          >
            View My Requests
          </Button>
        </Stack>
      </Box>
    </Container>
  );
}