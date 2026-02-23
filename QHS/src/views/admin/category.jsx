import { useState, useEffect } from "react";
import * as React from "react";
import { Link } from "react-router-dom";
import axiosClient from "../../axiosClient";
import moment from "moment";
// UI
import { styled } from "@mui/material/styles";
import TableCell, { tableCellClasses } from '@mui/material/TableCell';
import Table from "@mui/material/Table";
import TableBody from "@mui/material/TableBody";
import TableContainer from "@mui/material/TableContainer";
import TableHead from "@mui/material/TableHead";
import TableRow from "@mui/material/TableRow";
import Paper from "@mui/material/Paper";
import TablePagination from "@mui/material/TablePagination";
import Button from "@mui/material/Button";
import IconButton from '@mui/material/IconButton';
import AddIcon from "@mui/icons-material/Add";
import EditIcon from "@mui/icons-material/Edit";
import DeleteIcon from "@mui/icons-material/Delete";
import Dialog from "@mui/material/Dialog";
import DialogActions from "@mui/material/DialogActions";
import DialogContent from "@mui/material/DialogContent";
import DialogContentText from "@mui/material/DialogContentText";
import DialogTitle from "@mui/material/DialogTitle";
import { Box, TextField } from "@mui/material";
import { Avatar, Typography } from "@mui/material";
import { getInitials } from "../../utils";

export default function Category() {
  const [categories, setCategories] = useState([]);
  const [filteredCategories, setFilteredCategories] = useState([]);
  const [loading, setLoading] = useState(false);
  const [page, setPage] = useState(0);
  const [rowsPerPage, setRowsPerPage] = useState(10);
  const [openDeleteDialog, setOpenDeleteDialog] = useState(false);
  const [openCategoryModal, setOpenCategoryModal] = useState(false);
  const [selectedCategory, setSelectedCategory] = useState(null);
  const [query, setQuery] = useState("");
  const [categoryForm, setCategoryForm] = useState({
    id: null,
    name: "",
  });

  useEffect(() => {
    getCategories();
  }, []);

  const getCategories = () => {
    setLoading(true);
    axiosClient
      .get("/categories")
      .then(({ data }) => {
        setLoading(false);
        setCategories(data.data);
        setFilteredCategories(data.data);
      })
      .catch(() => {
        setLoading(false);
      });
  };

  const onDeleteClick = (category) => {
    axiosClient.delete(`categories/${category.id}`).then(() => {
      getCategories();
      handleCloseDeleteDialog();
    });
  };

  const handleChangePage = (event, newPage) => {
    setPage(newPage);
  };

  const handleChangeRowsPerPage = (event) => {
    setRowsPerPage(parseInt(event.target.value, 10));
    setPage(0);
  };

  const handleOpenDeleteDialog = (category) => {
    setSelectedCategory(category);
    setOpenDeleteDialog(true);
  };

  const handleCloseDeleteDialog = () => {
    setOpenDeleteDialog(false);
    setSelectedCategory(null);
  };

  const handleOpenCategoryModal = (category = null) => {
    if (category) {
      setCategoryForm({
        id: category.id,
        name: category.name,
      });
    } else {
      setCategoryForm({
        id: null,
        name: "",
      });
    }
    setOpenCategoryModal(true);
  };

  const handleCloseCategoryModal = () => {
    setOpenCategoryModal(false);
    setCategoryForm({
      id: null,
      name: "",
    });
  };

  const handleCategoryFormChange = (e) => {
    setCategoryForm({
      ...categoryForm,
      [e.target.name]: e.target.value,
    });
  };

  const handleCategoryFormSubmit = () => {
    if (categoryForm.id) {
      // Update existing category
      axiosClient
        .put(`/categories/${categoryForm.id}`, categoryForm)
        .then(() => {
          getCategories();
          handleCloseCategoryModal();
        })
        .catch((error) => {
          console.error("Error updating category:", error);
        });
    } else {
      // Create new category
      axiosClient
        .post("/categories", categoryForm)
        .then(() => {
          getCategories();
          handleCloseCategoryModal();
        })
        .catch((error) => {
          console.error("Error creating category:", error);
        });
    }
  };

  const searchData = (data) => {
    return data.filter(
      (item) =>
        item.name.toLowerCase().includes(query.toLowerCase()) ||
        item.id.toString().includes(query)
    );
  };

  return (
    <div>
      <TableContainer component={Paper}>
        <Table>
          <TableRow>
            <TableCell sx={{ maxWidth: '250px' }}>
              <Typography variant="h9" sx={{ fontSize: '2.5vh', color: 'Maroon', overflowWrap: "break-word" }}>
                List of Categories
              </Typography>
              <Typography sx={{ color: 'gray', overflowWrap: "break-word" }}>
                A list of all categories. Here you can create, edit, and
                remove categories.
              </Typography>
            </TableCell>
            <TableCell align="center">
              <input
                type="text"
                placeholder="Search Categories..."
                onChange={(e) => setQuery(e.target.value)}
                elevation={6} />
            </TableCell>
            <TableCell align="right">
              <Button
                variant="contained"
                startIcon={<AddIcon />}
                sx={{ backgroundColor: "white", color: "maroon" }}
                onClick={() => handleOpenCategoryModal()}
              >
                Add new Category
              </Button>
            </TableCell>
          </TableRow>
        </Table>
      </TableContainer>

      {/* Table with sticky header */}
      <TableContainer component={Paper} elevation={3} 
      sx={{ 
        maxHeight: 'calc(93vh - 200px)',
       }}>
        <Table aria-label="sticky table" stickyHeader>
          <TableHead>
            <TableRow sx={{ "& th": { color: "White", backgroundColor: "maroon", position: 'sticky', top: 0, zIndex: 1 } }}>
              <TableCell>ID</TableCell>
              <TableCell>NAME</TableCell>
              <TableCell>CREATED</TableCell>
              <TableCell>UPDATED AT</TableCell>
              <TableCell>ACTIONS</TableCell>
            </TableRow>
          </TableHead>
          {loading && (
            <TableBody>
              <TableRow>
                <TableCell colSpan={5} align="center">
                  Fetching Data ...
                </TableCell>
              </TableRow>
            </TableBody>
          )}
          {!loading && (
            <TableBody>
              {filteredCategories.length === 0 ? (
                <TableRow>
                  <TableCell colSpan={5} align="center">
                    No Category Found
                  </TableCell>
                </TableRow>
              ) : (
                searchData(filteredCategories).slice(page * rowsPerPage, (page + 1) * rowsPerPage).map((c) => (
                  <TableRow key={c.id}>
                    <TableCell>{c.id}</TableCell>
                    <TableCell>{c.name}</TableCell>
                    <TableCell>{moment(c.created_at).format("MM/DD/yyyy HH:mm:ss")}</TableCell>
                    <TableCell>{moment(c.updated_at).format("MM/DD/yyyy HH:mm:ss")}</TableCell>
                    <TableCell>
                      <Box sx={{ display: 'flex', gap: .5 }}>
                        <IconButton
                          color="primary"
                          aria-label="Edit"
                          size="large"
                          onClick={() => handleOpenCategoryModal(c)}
                        >
                          <EditIcon />
                        </IconButton>
                        <IconButton
                          color="error"
                          aria-label="Delete"
                          size="large"
                          onClick={() => handleOpenDeleteDialog(c)}
                        >
                          <DeleteIcon />
                        </IconButton>
                      </Box>
                    </TableCell>
                  </TableRow>
                ))
              )}
            </TableBody>
          )}
        </Table>
      </TableContainer>
      <TablePagination
          component="div"
          count={filteredCategories.length}
          page={page}
          onPageChange={handleChangePage}
          rowsPerPage={rowsPerPage}
          onRowsPerPageChange={handleChangeRowsPerPage}
          rowsPerPageOptions={[5, 10, 20, 50]}
        />

      {/* Delete Confirmation Dialog */}
      <Dialog
        open={openDeleteDialog}
        onClose={handleCloseDeleteDialog}
        aria-labelledby="alert-dialog-title"
        aria-describedby="alert-dialog-description"
      >
        <DialogTitle id="alert-dialog-title" color="error">
          CATEGORY DELETION
        </DialogTitle>
        <DialogContent>
          <DialogContentText id="alert-dialog-description">
            Are you sure you want to delete this category?
          </DialogContentText>
        </DialogContent>
        <DialogActions>
          <Button onClick={handleCloseDeleteDialog}>No</Button>
          <Button onClick={() => onDeleteClick(selectedCategory)} autoFocus>
            Yes
          </Button>
        </DialogActions>
      </Dialog>

      {/* Add/Edit Category Modal */}
      <Dialog
        open={openCategoryModal}
        onClose={handleCloseCategoryModal}
        aria-labelledby="form-dialog-title"
      >
        <DialogTitle id="form-dialog-title">
          {categoryForm.id ? "Edit Category" : "Add New Category"}
        </DialogTitle>
        <DialogContent>
          <TextField
            autoFocus
            margin="dense"
            name="name"
            label="Category Name"
            type="text"
            fullWidth
            value={categoryForm.name}
            onChange={handleCategoryFormChange}
          />
        </DialogContent>
        <DialogActions>
          <Button onClick={handleCloseCategoryModal} color="primary">
            Cancel
          </Button>
          <Button onClick={handleCategoryFormSubmit} color="primary">
            {categoryForm.id ? "Update" : "Save"}
          </Button>
        </DialogActions>
      </Dialog>
    </div>
  );
}