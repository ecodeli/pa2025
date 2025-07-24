const express = require('express');
const app = express();
const cors = require('cors');
const PORT = process.env.PORT || 8001;
const path = require('path');


const userRoutes = require('./routes/user');
const annonceRoutes = require('./routes/annonce');
const marketplaceRoutes = require('./routes/marketplace');
const likeRoutes = require('./routes/like');
const messageRoutes = require('./routes/message');
const adminRoutes = require('./routes/admin');
const warehouseRoutes = require('./routes/warehouse');
const adminListingRoutes = require('./routes/adminListing');
const walletRoutes = require('./routes/wallet');
//const delivery = require('./routes/delivery');
const trajetsRoutes = require('./routes/trajets');
const livraisonRoutes = require('./routes/livraison');
const factureRoutes = require('./routes/facture');
const deliveryRoutes = require('./routes/deliveryRoutes');
const adminFactureRoutes = require('./routes/adminFacture');
const documentRoutes = require('./routes/document');
const userDocumentRoutes = require('./routes/userDocument');
const availabilityRoutes = require('./routes/availabilityRoutes');
const reviewRoutes = require('./routes/review');
const dashboardRoutes = require('./routes/dashboard');
const serviceRoutes = require('./routes/serviceRoutes');
const bookingRoutes = require('./routes/bookingRoutes');
const abonnementRoutes = require('./routes/abonnement');
const notificationsRoutes = require('./routes/notifications');
const storageRoutes=require('./routes/storage');
const mesboxRoutes = require('./routes/mesbox');
const adminBoxRoutes = require('./routes/adminBox');
const nfcRoutes = require('./routes/nfc');
const exportRoutes = require("./routes/export");
const deliveryProgress = require('./routes/deliveryProgress');



require('dotenv').config();

app.use(express.json());
app.use(cors({
  origin: true,
  credentials: true
}));
app.use(express.urlencoded({ extended: true }));

app.use('/Documents', express.static('Documents'));
app.use('/api', walletRoutes);
app.use('/api', adminRoutes);
app.use('/api', adminListingRoutes);
app.use('/api', userRoutes);
app.use('/api', annonceRoutes);
app.use('/uploads', express.static('uploads'));
app.use('/api', marketplaceRoutes);
app.use('/api', likeRoutes );
app.use('/api/messages', messageRoutes);
app.use('/api/warehouses', warehouseRoutes);
app.use('/api/delivery', deliveryRoutes); //ne pas toucher pls
app.use('/api/trajets', trajetsRoutes);
app.use('/api/livraison', livraisonRoutes);
app.use('/invoices', express.static(path.join(__dirname, 'invoices')));
app.use('/api/factures', factureRoutes);
app.use('/api/adminFacture', adminFactureRoutes);
app.use("/api/availabilities", availabilityRoutes);
app.use('/api', documentRoutes);
app.use('/api', userDocumentRoutes);
app.use('/api', reviewRoutes);
app.use('/api', dashboardRoutes);
app.use('/api/service', serviceRoutes);
app.use('/api/bookings', bookingRoutes)
app.use('/api/abonnement', abonnementRoutes);
app.use('/api/notifications',notificationsRoutes);
app.use('/api/storage',storageRoutes);
app.use('/api', mesboxRoutes);
app.use('/api', adminBoxRoutes);
app.use('/api/nfc', nfcRoutes);
app.use('/api', require('./routes/identity'));
app.use("/api/export", exportRoutes);
app.use('/api', deliveryProgress);



app.listen(PORT, '0.0.0.0', () => {
  console.log(`Serveur API démarré sur le port ${PORT}`);
});


