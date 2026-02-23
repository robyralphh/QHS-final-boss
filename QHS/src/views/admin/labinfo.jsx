import { useEffect, useState } from "react";
import { Link, useNavigate, useParams } from "react-router-dom";
import axiosClient from "../../axiosClient";
import ArrowBackIcon from "@mui/icons-material/ArrowBack";
import Button from "@mui/material/Button";
import MenuItem from '@mui/material/MenuItem';
import Select from '@mui/material/Select';
import InputLabel from '@mui/material/InputLabel';
import FormControl from '@mui/material/FormControl';
import { Typography } from "@mui/material";

export default function LabInfo() {
  const { id } = useParams();
  const navigate = useNavigate();
  const [laboratory, setLaboratory] = useState({
    id: null,
    name: "",
    location: "",
    description: "",
    custodianID: null, // single custodian user ID
  });

  const [loading, setLoading] = useState(false);
  const [custodians, setCustodians] = useState([]);
  const [selectedCustodian, setSelectedCustodian] = useState(null);
  const [errors, setErrors] = useState({});

  // Fetch laboratory details if id is present
  useEffect(() => {
    if (id) {
      setLoading(true);
      axiosClient
        .get(`/laboratories/${id}`)
        .then(({ data }) => {
          setLoading(false);
          setLaboratory(data);
          setSelectedCustodian(data.custodianID || null);
        })
        .catch(() => {
          setLoading(false);
        });
    }
  }, [id]);

  // Fetch custodians when the component mounts
  useEffect(() => {
    getUsers();
  }, []);

  const getUsers = () => {
    setLoading(true);
    axiosClient
      .get("/users", { params: { role: "custodian" } })
      .then(({ data }) => {
        setLoading(false);
        // Filter out users who are already custodians of another lab (unless they're already assigned to this lab)
        const allCustodians = data.data;
        axiosClient.get("/laboratories").then(({ data: labsData }) => {
          const labs = labsData.data || [];
          const assignedIds = labs
            .filter(lab => lab.id !== Number(id))
            .map(lab => lab.custodianID)
            .filter(ID => ID !== null);
          const filtered = allCustodians.filter(
            user => !assignedIds.includes(user.id) || selectedCustodian === user.id
          );
          setCustodians(filtered);
        });
      })
      .catch(() => {
        setLoading(false);
      });
  };

  const onSubmit = (ev) => {
    ev.preventDefault();
    const payload = {
      ...laboratory,
      custodianID: selectedCustodian,
    };
    delete payload.gallery;
    if (laboratory.id) {
      axiosClient
        .put(`/laboratories/${laboratory.id}`, payload)
        .then(() => {
          navigate("../lab");
        })
        .catch((err) => {
          const response = err.response;
          console.error("Error Response:", response);
          if (response && response.status === 422) {
            if (response.data.message) {
              alert(response.data.message);
            } else {
              setErrors(response.data.errors);
            }
          }
        });
    } else {
      navigate("../lab");
    }
  };

  const handleCustodianChange = (event) => {
    setSelectedCustodian(event.target.value);
  };

  return (
    <>
      {/* Back Button */}
      <Link to="../lab">
        <Button
          variant="outlined"
          startIcon={<ArrowBackIcon />}
          sx={{
            color: "maroon",
            borderColor: "maroon",
            marginBottom: 2,
            "&:hover": {
              borderColor: "maroon",
              backgroundColor: "rgba(128, 0, 0, 0.04)",
            },
          }}
        >
          Back
        </Button>
      </Link>
      {laboratory.id && <Typography variant="h4" sx={{ color:"maroon"}}>{laboratory.name}</Typography>}
      <Typography variant="body" sx={{ color:"gray"}}>{laboratory.description}</Typography>
      <div className="card animated fadeInDown">
        {!loading && (
          <form onSubmit={onSubmit}>
            <FormControl fullWidth>
              <InputLabel id="custodian-select-label">Custodian</InputLabel>
              <Select
                labelId="custodian-select-label"
                id="custodian-select"
                value={selectedCustodian || ""}
                label="Custodian"
                onChange={handleCustodianChange}
                renderValue={selected => {
                  if (!selected) return "No custodian assigned";
                  const user = custodians.find(c => c.id === selected);
                  return user ? user.name : selected;
                }}
              >
                <MenuItem value="">
                  <em>No Custodian</em>
                </MenuItem>
                {custodians.map((custodian) => (
                  <MenuItem key={custodian.id} value={custodian.id}>
                    {custodian.name}
                  </MenuItem>
                ))}
              </Select>
            </FormControl>
            <br />
            <br />
            <button className="btn" sx={{borderRadius:"20%"}}>SET</button>
          </form>
        )}
      </div>
    </>
  );
}