import { Navigate } from "react-router-dom";
import { useStateContext } from "../Context/ContextProvider";

const ProtectedRoute = ({ children, allowedRoles }) => {
    const { token, user } = useStateContext();

    if (!token) {
        return <Navigate to="/auth/login" replace />;
    }

    if (!allowedRoles.includes(user.role)) {
        return <Navigate to="/NotAuthorized" replace />;
    }


    return children;
};

export default ProtectedRoute;