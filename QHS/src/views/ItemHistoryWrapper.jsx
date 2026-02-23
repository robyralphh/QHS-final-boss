import { useEffect, useState } from 'react';
import { useParams, useNavigate } from 'react-router-dom';
import { useStateContext } from '../Context/ContextProvider';
import ItemHistoryPublic from './ItemHistoryPublic';
import { Box, Typography, Button, Paper, CircularProgress } from '@mui/material';

export default function ItemHistoryWrapper() {
  const { unitID } = useParams();
  const navigate = useNavigate();
  const { user, token, loading: authLoading } = useStateContext();
  const [isAuthorized, setIsAuthorized] = useState(false);
  const [checkingAccess, setCheckingAccess] = useState(true);

  useEffect(() => {
    // If not authenticated, show login prompt
    if (!authLoading && !token) {
      setCheckingAccess(false);
      return;
    }

    // If authenticated as admin or custodian, allow access
    if (user && (user.role === 'admin' || user.role === 'custodian')) {
      setIsAuthorized(true);
      setCheckingAccess(false);
      return;
    }

    setCheckingAccess(false);
  }, [user, token, authLoading]);

  // Still loading auth state
  if (authLoading) {
    return (
      <Box sx={{ display: 'flex', justifyContent: 'center', alignItems: 'center', minHeight: '80vh' }}>
        <CircularProgress />
      </Box>
    );
  }

  // Not authenticated - show login prompt
  if (!token) {
    return (
      <Box sx={{ display: 'flex', justifyContent: 'center', alignItems: 'center', minHeight: '80vh', p: 2 }}>
        <Paper sx={{ p: 4, maxWidth: 400, textAlign: 'center' }}>
          <Typography variant="h5" sx={{ mb: 2, fontWeight: 'bold' }}>
            Authentication Required
          </Typography>
          <Typography variant="body1" sx={{ mb: 4, color: 'text.secondary' }}>
            Please log in to view this item's borrowing history.
          </Typography>
          <Button
            variant="contained"
            size="large"
            sx={{ bgcolor: 'maroon', '&:hover': { bgcolor: 'darkred' } }}
            onClick={() => navigate(`/auth?next=/item-history/${unitID}`)}
          >
            Login to Continue
          </Button>
        </Paper>
      </Box>
    );
  }

  // Authenticated but not authorized
  if (!isAuthorized) {
    return (
      <Box sx={{ display: 'flex', justifyContent: 'center', alignItems: 'center', minHeight: '80vh', p: 2 }}>
        <Paper sx={{ p: 4, maxWidth: 400, textAlign: 'center' }}>
          <Typography variant="h5" sx={{ mb: 2, fontWeight: 'bold', color: 'error' }}>
            Access Denied
          </Typography>
          <Typography variant="body1" sx={{ mb: 4, color: 'text.secondary' }}>
            You don't have permission to view this item's history. Only administrators and custodians can access this information.
          </Typography>
          <Button
            variant="outlined"
            onClick={() => navigate('/')}
          >
            Go Back Home
          </Button>
        </Paper>
      </Box>
    );
  }

  // Authorized - show item history
  return <ItemHistoryPublic />;
}
