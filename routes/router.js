import { con } from '../index.js'; 
import express from "express";

const router = express.Router();


// GET /api2/drones
router.get("/", (req, res) => {
    const query = "SELECT * FROM drones";
    con.query(query, (err, result) => {
        if (err) {
            console.error("Error fetching drones:", err);
            return res.status(500).json({ message: "Error fetching data", error: err });
        }
        return res.json({ drones: result });
    });
});

// GET /api2/drones/:id
router.get("/:id", (req, res) => {
    const droneId = req.params.id;
    const query = "SELECT * FROM drones WHERE id = ?";
    con.query(query, [droneId], (err, result) => {
        if (err) {
            console.error("Error fetching drone:", err);
            return res.status(500).json({ message: "Error fetching data", error: err });
        }
        if (result.length === 0) {
            return res.status(404).json({ message: "Drone not found" });
        }
        return res.json({ drone: result[0] });
    });
});

// POST /api2/drones
router.post("/", (req, res) => {
    // Assuming the request body contains the drone data
    const { name, model } = req.body;
    const query = "INSERT INTO drones (name, model) VALUES (?, ?)";
    con.query(query, [name, model], (err, result) => {
        if (err) {
            console.error("Error creating drone:", err);
            return res.status(500).json({ message: "Error inserting data", error: err });
        }
        return res.status(201).json({ message: "Drone created successfully", id: result.insertId });
    });
});

// PUT /api2/drones/:id
router.put("/:id", (req, res) => {
    const droneId = req.params.id;
    const { name, model } = req.body;

    const query = "UPDATE drones SET name = ?, model = ? WHERE id = ?";
    con.query(query, [name, model, droneId], (err, result) => {
        if (err) {
            console.error("Error updating drone:", err);
            return res.status(500).json({ message: "Error updating data", error: err });
        }
        if (result.affectedRows === 0) {
            return res.status(404).json({ message: "Drone not found" });
        }
        return res.json({ message: "Drone updated successfully" });
    });
});

// DELETE /api2/drones/:id
router.delete("/:id", (req, res) => {
    const droneId = req.params.id;
    const query = "DELETE FROM drones WHERE id = ?";
    con.query(query, [droneId], (err, result) => {
        if (err) {
            console.error("Error deleting drone:", err);
            return res.status(500).json({ message: "Error deleting data", error: err });
        }
        if (result.affectedRows === 0) {
            return res.status(404).json({ message: "Drone not found" });
        }
        return res.json({ message: "Drone deleted successfully" });
    });
});

// Implement PUT and DELETE endpoints similarly (zie volgende stappen)

export default router; // Exporteer de router