import { Component } from '@angular/core';
import { RouterOutlet, RouterLink, Router } from '@angular/router';
import { AuthService } from './services/auth.service';
import { CommonModule } from '@angular/common'; // Necessário para o *ngIf

@Component({
  selector: 'app-root',
  standalone: true,
  imports: [RouterOutlet, RouterLink, CommonModule],
  templateUrl: './app.html',
})
export class AppComponent {
  constructor(public authService: AuthService, private router: Router) {}

  sair() {
    this.authService.logout(); // Limpa a sessão
    this.router.navigate(['/']); // Vai para o Login
  }
}
